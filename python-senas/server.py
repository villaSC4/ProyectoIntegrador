from __future__ import annotations

import json
import math
import os
import statistics
import threading
import time
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from typing import Any

MODEL_VERSION = "v4.0"
ENGINE_BUILD = "postura-20260713-2"
NEGATIVE_CODE = "__ninguna_sena__"
MINIMUM_SAMPLES_PER_PHRASE = 8
MINIMUM_CONFIDENCE = 0.70
SAMPLE_FRAMES = 12
MAX_DTW_CANDIDATES = 18
MAX_DTW_SAMPLES_PER_CLASS = 3
MAX_HARD_REJECTION_CANDIDATES = 8
HARD_REJECTION_DISTANCE = 0.18

MODEL_LOCK = threading.RLock()
MODEL_SAMPLES: list[dict[str, Any]] = []
MODEL_REVISION = ""


def clamp(value: float) -> float:
    return max(0.0, min(1.0, value))


def point_distance(a: dict[str, float], b: dict[str, float]) -> float:
    return math.sqrt(
        ((a.get("x", 0.0) - b.get("x", 0.0)) ** 2)
        + ((a.get("y", 0.0) - b.get("y", 0.0)) ** 2)
        + ((a.get("z", 0.0) - b.get("z", 0.0)) ** 2)
    )


def sample_items(items: list[Any], amount: int) -> list[Any]:
    total = len(items)
    if total == 0:
        return []
    if total <= amount:
        result = list(items)
        while len(result) < amount:
            result.append(result[-1])
        return result
    return [items[round(i * (total - 1) / max(1, amount - 1))] for i in range(amount)]


def parse_json(value: Any, default: Any = None) -> Any:
    if isinstance(value, (dict, list)):
        return value
    if not isinstance(value, str) or not value.strip():
        return default
    try:
        return json.loads(value)
    except json.JSONDecodeError:
        return default


def learning_metadata(sample: dict[str, Any], features: dict[str, Any]) -> dict[str, Any]:
    metadata = features.get("aprendizaje")
    if isinstance(metadata, dict):
        return metadata
    metadata = parse_json(sample.get("metricas"), {})
    return metadata if isinstance(metadata, dict) else {}


def hand_scale(hand: list[dict[str, float]]) -> float:
    return max(0.0001, point_distance(hand[0], hand[9]))


def hand_center(hand: list[dict[str, float]]) -> dict[str, float]:
    indexes = [0, 5, 9, 13, 17]
    return {
        "x": sum(hand[i].get("x", 0.0) for i in indexes) / len(indexes),
        "y": sum(hand[i].get("y", 0.0) for i in indexes) / len(indexes),
        "z": sum(hand[i].get("z", 0.0) for i in indexes) / len(indexes),
    }


def normalize_hand(hand: list[dict[str, float]]) -> list[dict[str, float]]:
    wrist = hand[0]
    scale = hand_scale(hand)
    angle = math.atan2(
        hand[9].get("y", 0.0) - wrist.get("y", 0.0),
        hand[9].get("x", 0.0) - wrist.get("x", 0.0),
    )
    cos_v = math.cos(-angle)
    sin_v = math.sin(-angle)
    result = []
    for point in hand:
        x = (point.get("x", 0.0) - wrist.get("x", 0.0)) / scale
        y = (point.get("y", 0.0) - wrist.get("y", 0.0)) / scale
        result.append(
            {
                "x": (x * cos_v) - (y * sin_v),
                "y": (x * sin_v) + (y * cos_v),
                "z": (point.get("z", 0.0) - wrist.get("z", 0.0)) / scale,
            }
        )
    return result


def hand_shape_distance(a: list[dict[str, float]], b: list[dict[str, float]]) -> float:
    na = normalize_hand(a)
    nb = normalize_hand(b)
    weights = {4: 1.4, 8: 1.8, 12: 1.8, 16: 1.7, 20: 1.7}
    total = 0.0
    weight_total = 0.0
    for i in range(21):
        weight = weights.get(i, 1.0)
        total += (
            ((na[i]["x"] - nb[i]["x"]) ** 2)
            + ((na[i]["y"] - nb[i]["y"]) ** 2)
            + (((na[i]["z"] - nb[i]["z"]) * 0.65) ** 2)
        ) * weight
        weight_total += weight
    return math.sqrt(total / max(1.0, weight_total))


def hands_separation(hands: list[list[dict[str, float]]]) -> float:
    if len(hands) < 2:
        return 0.0
    scale = max(0.02, (hand_scale(hands[0]) + hand_scale(hands[1])) / 2)
    return point_distance(hand_center(hands[0]), hand_center(hands[1])) / scale


def hand_matching_distance(
    a: list[list[dict[str, float]]], b: list[list[dict[str, float]]]
) -> float:
    amount = min(len(a), len(b))
    if amount == 0:
        return 0.0
    return sum(hand_shape_distance(a[i], b[i]) for i in range(amount)) / amount


def context_distance(a: dict[str, Any], b: dict[str, Any]) -> float:
    face_a = a.get("rostro") or {}
    face_b = b.get("rostro") or {}
    body_a = a.get("cuerpo") or {}
    body_b = b.get("cuerpo") or {}
    values = []
    for key, weight in (("yaw", 2.0), ("pitch", 2.0), ("boca", 1.0), ("cejas", 1.0)):
        if key in face_a and key in face_b:
            values.append(abs(float(face_a[key]) - float(face_b[key])) * weight)
    for key, weight in (("inclinacion", 2.0), ("hombro_y", 1.0)):
        if key in body_a and key in body_b:
            values.append(abs(float(body_a[key]) - float(body_b[key])) * weight)
    return sum(values) / len(values) if values else 0.0


def body_zone_map(frame: dict[str, Any]) -> dict[str, Any]:
    face = frame.get("rostro") or {}
    body = frame.get("cuerpo") or {}
    shoulder_width = max(0.0, float(body.get("ancho_hombros", 0.0) or 0.0))
    face_scale = max(
        float(face.get("escala_rostro", 0.0) or 0.0),
        shoulder_width * 0.42 if shoulder_width > 0 else 0.0,
    )
    scale = max(0.12, shoulder_width, face_scale * 1.9)
    zones: dict[str, dict[str, float]] = {}

    if "x" in face and "y" in face:
        x = float(face["x"])
        y = float(face["y"])
        z = float(face.get("z", 0.0) or 0.0)
        radius = max(0.055, face_scale)
        zones["boca"] = {
            "x": float(face.get("boca_x", x)),
            "y": float(face.get("boca_y", y + radius * 0.30)),
            "z": float(face.get("boca_z", z)),
        }
        zones["frente"] = {
            "x": float(face.get("frente_x", x)),
            "y": float(face.get("frente_y", y - radius * 0.42)),
            "z": float(face.get("frente_z", z)),
        }
        zones["cachete_izq"] = {
            "x": float(face.get("cachete_izq_x", x - radius * 0.48)),
            "y": float(face.get("cachete_izq_y", y + radius * 0.08)),
            "z": float(face.get("cachete_izq_z", z)),
        }
        zones["cachete_der"] = {
            "x": float(face.get("cachete_der_x", x + radius * 0.48)),
            "y": float(face.get("cachete_der_y", y + radius * 0.08)),
            "z": float(face.get("cachete_der_z", z)),
        }

    if "hombro_x" in body and "hombro_y" in body:
        x = float(body["hombro_x"])
        y = float(body["hombro_y"])
        half = max(0.06, shoulder_width / 2)
        zones["hombro_izq"] = {
            "x": float(body.get("hombro_izq_x", x - half)),
            "y": float(body.get("hombro_izq_y", y)),
            "z": float(body.get("hombro_izq_z", 0.0)),
        }
        zones["hombro_der"] = {
            "x": float(body.get("hombro_der_x", x + half)),
            "y": float(body.get("hombro_der_y", y)),
            "z": float(body.get("hombro_der_z", 0.0)),
        }
        zones["pecho"] = {
            "x": float(body.get("pecho_x", x)),
            "y": float(body.get("pecho_y", y + max(0.12, shoulder_width) * 0.55)),
            "z": float(body.get("pecho_z", 0.0)),
        }

    return {"zones": zones, "scale": scale}


def body_relation_vector(
    frame: dict[str, Any],
    hand: list[dict[str, float]],
    zone_map: dict[str, Any] | None = None,
) -> dict[str, float]:
    zone_map = zone_map or body_zone_map(frame)
    zones = zone_map["zones"]
    if not zones:
        return {}
    result = {}
    indexes = [0, 4, 8, 12, 16, 20, 5, 9, 13, 17]
    for name, zone in zones.items():
        minimum = math.inf
        for index in indexes:
            point = hand[index]
            dx = point.get("x", 0.0) - zone["x"]
            dy = point.get("y", 0.0) - zone["y"]
            dz = (point.get("z", 0.0) - zone.get("z", 0.0)) * 0.20
            minimum = min(
                minimum,
                math.sqrt((dx * dx) + (dy * dy) + (dz * dz)) / zone_map["scale"],
            )
        if math.isfinite(minimum):
            result[name] = min(3.0, minimum)
    return result


def body_relations(frame: dict[str, Any]) -> list[dict[str, float]]:
    zone_map = body_zone_map(frame)
    return [
        body_relation_vector(frame, hand, zone_map) for hand in frame.get("manos", [])
    ]


def relation_distance(a: dict[str, Any], b: dict[str, Any]) -> float:
    rel_a = a.get("relaciones_corporales") or body_relations(a)
    rel_b = b.get("relaciones_corporales") or body_relations(b)
    if not rel_a or not rel_b:
        return 0.0

    def ordered(left: list[dict[str, float]], right: list[dict[str, float]]) -> float:
        amount = min(len(left), len(right))
        total = 0.0
        comparisons = 0
        for i in range(amount):
            for zone in set(left[i]).intersection(right[i]):
                total += min(1.5, abs(left[i][zone] - right[i][zone]))
                comparisons += 1
        return total / comparisons if comparisons else 0.0

    direct = ordered(rel_a, rel_b)
    if len(rel_a) == 2 and len(rel_b) == 2:
        return min(direct, ordered(rel_a, list(reversed(rel_b))))
    return direct


def frame_distance(a: dict[str, Any], b: dict[str, Any]) -> float:
    hands_a = a.get("manos") or []
    hands_b = b.get("manos") or []
    if not hands_a and not hands_b:
        return context_distance(a, b)
    if not hands_a or not hands_b:
        return 0.85 + context_distance(a, b) * 0.15

    direct = hand_matching_distance(hands_a, hands_b)
    if len(hands_a) == 2 and len(hands_b) == 2:
        direct = min(direct, hand_matching_distance(hands_a, list(reversed(hands_b))))

    amount_penalty = abs(len(hands_a) - len(hands_b)) * 0.28
    relation = abs(hands_separation(hands_a) - hands_separation(hands_b)) * 1.4
    body_relation = relation_distance(a, b)
    return (
        (direct * 0.64)
        + (relation * 0.12)
        + (context_distance(a, b) * 0.08)
        + (body_relation * 0.16)
        + amount_penalty
    )


def add_body_relations(sequence: list[dict[str, Any]]) -> list[dict[str, Any]]:
    enriched = []
    for frame in sequence:
        item = dict(frame)
        item["relaciones_corporales"] = body_relations(item)
        enriched.append(item)
    return enriched


def dtw_distance(actual: list[dict[str, Any]], sample: list[dict[str, Any]]) -> float:
    actual = add_body_relations(sample_items(actual, 14))
    sample = add_body_relations(sample_items(sample, 14))
    n = len(actual)
    m = len(sample)
    if not n or not m:
        return 9.0

    costs = [[math.inf] * (m + 1) for _ in range(n + 1)]
    steps = [[10**9] * (m + 1) for _ in range(n + 1)]
    costs[0][0] = 0.0
    steps[0][0] = 0

    for i in range(1, n + 1):
        for j in range(1, m + 1):
            best_cost, best_steps = min(
                (costs[i - 1][j], steps[i - 1][j]),
                (costs[i][j - 1], steps[i][j - 1]),
                (costs[i - 1][j - 1], steps[i - 1][j - 1]),
                key=lambda item: item[0],
            )
            costs[i][j] = best_cost + frame_distance(actual[i - 1], sample[j - 1])
            steps[i][j] = best_steps + 1

    return costs[n][m] / max(1, steps[n][m])


def vector_distance(a: list[float], b: list[float]) -> float:
    amount = min(len(a), len(b))
    if amount == 0:
        return 9.0
    total = sum((float(a[i]) - float(b[i])) ** 2 for i in range(amount))
    size_difference = abs(len(a) - len(b)) / max(1, len(a), len(b))
    return math.sqrt(total / amount) + (size_difference * 0.3)


def compact_frame_descriptor(frame: dict[str, Any]) -> list[float]:
    result: list[float] = []
    hands = frame.get("manos") or []
    for hand_index in (0, 1):
        if hand_index >= len(hands):
            result.extend([0.0] * 39)
            continue
        hand = hands[hand_index]
        normalized = normalize_hand(hand)
        for index in (0, 4, 8, 12, 16, 20, 5, 9, 13, 17):
            result.extend(
                [normalized[index]["x"], normalized[index]["y"], normalized[index]["z"]]
            )
        scale = hand_scale(hand)
        for a, b in (
            (4, 8),
            (8, 12),
            (12, 16),
            (16, 20),
            (8, 20),
            (4, 20),
            (0, 12),
            (5, 17),
            (0, 9),
        ):
            result.append(point_distance(hand[a], hand[b]) / scale)
    result.extend(context_frame(frame))
    return result


def dominant_hand_count(sequence: list[dict[str, Any]]) -> int:
    counts: dict[int, int] = {}
    for frame in sequence:
        amount = min(2, len(frame.get("manos") or []))
        if amount:
            counts[amount] = counts.get(amount, 0) + 1
    if not counts:
        return 0
    # En un empate se prefiere una mano: evita que un falso segundo rastreo de
    # pocos cuadros convierta una letra de una mano en una seña de dos manos.
    return max(counts, key=lambda amount: (counts[amount], -amount))


def canonical_hand_vector(hand: list[dict[str, float]]) -> list[float]:
    normalized = normalize_hand(hand)
    # Tras alinear muneca y nudillo medio, el eje lateral cambia de signo entre
    # mano izquierda y derecha. Canonizarlo permite aprender la misma seña con
    # cualquiera de las dos manos.
    mirror = -1.0 if normalized[5]["y"] > normalized[17]["y"] else 1.0
    result: list[float] = []
    for point in normalized:
        result.extend([point["x"], point["y"] * mirror, point["z"]])

    scale = hand_scale(hand)
    for a, b in (
        (4, 8),
        (8, 12),
        (12, 16),
        (16, 20),
        (4, 20),
        (8, 20),
        (0, 4),
        (0, 8),
        (0, 12),
        (0, 16),
        (0, 20),
    ):
        result.append(point_distance(hand[a], hand[b]) / scale)
    return result


def posture_descriptor(
    sequence: list[dict[str, Any]],
) -> tuple[list[float], int]:
    amount = dominant_hand_count(sequence)
    if amount == 0:
        return [], 0

    vectors: list[list[float]] = []
    for frame in sequence:
        hands = frame.get("manos") or []
        if len(hands) != amount:
            continue
        ordered = sorted(hands, key=lambda hand: hand_center(hand)["x"])
        vector: list[float] = []
        for hand in ordered:
            vector.extend(canonical_hand_vector(hand))
        vectors.append(vector)

    if not vectors:
        return [], 0
    return [statistics.median(values) for values in zip(*vectors)], amount


def posture_distance(
    descriptor_a: list[float],
    hands_a: int,
    descriptor_b: list[float],
    hands_b: int,
) -> float:
    return vector_distance(descriptor_a, descriptor_b) + (
        abs(hands_a - hands_b) * 0.32
    )


def prepare_model_sample(sample: dict[str, Any]) -> dict[str, Any]:
    if sample.get("_python_prepared"):
        return sample

    features = parse_json(sample.get("features"), {})
    points = parse_json(sample.get("puntos"), [])
    if not isinstance(features, dict):
        features = {}
    if not isinstance(points, list):
        points = []

    posture, hands = posture_descriptor(points)
    features["postura_python"] = posture
    features["manos_dominantes_python"] = hands
    sample["features"] = features
    sample["puntos"] = points
    sample["_python_prepared"] = True
    return sample


def prepare_model_samples(samples: list[dict[str, Any]]) -> list[dict[str, Any]]:
    return [prepare_model_sample(dict(sample)) for sample in samples]


def sequence_descriptor(sequence: list[dict[str, Any]]) -> list[float]:
    result: list[float] = []
    for frame in sample_items(sequence, SAMPLE_FRAMES):
        result.extend(compact_frame_descriptor(frame))
    return [round(value, 5) for value in result]


def trajectory_descriptor(sequence: list[dict[str, Any]]) -> list[float]:
    result: list[float] = []
    origins: dict[int, dict[str, float]] = {}
    for frame in sample_items(sequence, SAMPLE_FRAMES):
        hands = frame.get("manos") or []
        for index in (0, 1):
            center = (
                hand_center(hands[index])
                if index < len(hands)
                else {"x": 0.0, "y": 0.0, "z": 0.0}
            )
            origins.setdefault(index, center)
            scale = max(
                0.12,
                float((frame.get("cuerpo") or {}).get("ancho_hombros", 0.28) or 0.28),
            )
            result.extend(
                [
                    (center["x"] - origins[index]["x"]) / scale,
                    (center["y"] - origins[index]["y"]) / scale,
                    (center["z"] - origins[index]["z"]) / scale,
                ]
            )
        face = frame.get("rostro") or {}
        result.extend(
            [
                len(hands) / 2,
                hands_separation(hands),
                float(face.get("yaw", 0.0) or 0.0),
                float(face.get("pitch", 0.0) or 0.0),
                float(face.get("boca", 0.0) or 0.0),
            ]
        )
    return [round(value, 5) for value in result]


def context_frame(frame: dict[str, Any]) -> list[float]:
    hands = frame.get("manos") or []
    face = frame.get("rostro") or {}
    body = frame.get("cuerpo") or {}
    return [
        len(hands) / 2,
        hands_separation(hands),
        float(face.get("yaw", 0.0) or 0.0),
        float(face.get("pitch", 0.0) or 0.0),
        float(face.get("boca", 0.0) or 0.0),
        float(face.get("cejas", 0.0) or 0.0),
        float(body.get("inclinacion", 0.0) or 0.0),
        float(body.get("ancho_hombros", 0.0) or 0.0),
    ]


def hand_motion(sequence: list[dict[str, Any]]) -> float:
    previous: dict[int, dict[str, Any]] = {}
    changes = []
    for frame in sequence:
        for index, hand in enumerate(frame.get("manos") or []):
            center = hand_center(hand)
            if index in previous:
                movement = point_distance(center, previous[index]["center"]) / max(
                    0.025, hand_scale(hand)
                )
                shape = hand_shape_distance(hand, previous[index]["hand"])
                changes.append(min(1.0, (movement * 0.42) + (shape * 1.25)))
            previous[index] = {"center": center, "hand": hand}
    if not changes:
        return 0.0
    changes.sort()
    relevant = changes[math.floor(len(changes) * 0.35) :]
    return min(1.0, (sum(relevant) / max(1, len(relevant))) * 1.8)


def face_motion(sequence: list[dict[str, Any]]) -> float:
    previous = None
    changes = []
    for frame in sequence:
        face = frame.get("rostro")
        if not face:
            continue
        if previous:
            changes.append(
                min(
                    1.0,
                    abs(
                        float(face.get("yaw", 0.0) or 0.0)
                        - float(previous.get("yaw", 0.0) or 0.0)
                    )
                    * 6
                    + abs(
                        float(face.get("pitch", 0.0) or 0.0)
                        - float(previous.get("pitch", 0.0) or 0.0)
                    )
                    * 6
                    + abs(
                        float(face.get("boca", 0.0) or 0.0)
                        - float(previous.get("boca", 0.0) or 0.0)
                    )
                    * 2,
                )
            )
        previous = face
    return min(1.0, (sum(changes) / len(changes)) * 2.2) if changes else 0.0


def analyze_sequence(sequence: list[dict[str, Any]]) -> dict[str, Any]:
    total = len(sequence)
    visible_frames = sum(1 for frame in sequence if len(frame.get("manos") or []) > 0)
    two_hand_frames = sum(1 for frame in sequence if len(frame.get("manos") or []) >= 2)
    max_hands = max(
        [min(2, len(frame.get("manos") or [])) for frame in sequence] or [0]
    )
    duration = int(
        max(
            0.0,
            (sequence[-1].get("t", 0.0) if sequence else 0.0)
            - (sequence[0].get("t", 0.0) if sequence else 0.0),
        )
    )
    motion_hands = hand_motion(sequence)
    motion_face = face_motion(sequence)
    motion = min(1.0, (motion_hands * 0.82) + (motion_face * 0.18))
    visibility_ratio = visible_frames / total if total else 0.0
    two_hand_ratio = two_hand_frames / total if total else 0.0
    quality = min(
        1.0,
        (min(1.0, visible_frames / 18) * 0.35)
        + (
            (
                1.0
                if 650 <= duration <= 6500
                else (
                    min(1.0, duration / 650)
                    if duration < 650
                    else max(0.35, 1 - ((duration - 6500) / 7000))
                )
            )
            * 0.25
        )
        + ((min(1.0, visibility_ratio / 0.72) if visible_frames else 0.0) * 0.30)
        + 0.10,
    )
    return {
        "calidad": round(quality, 4),
        "duracion_ms": duration,
        "cantidad_frames": total,
        "frames_visibles": visible_frames,
        "ratio_visibilidad": round(visibility_ratio, 4),
        "ratio_dos_manos": round(two_hand_ratio, 4),
        "manos_max": max_hands,
        "movimiento": round(motion, 4),
        "movimiento_manos": round(motion_hands, 4),
        "movimiento_rostro": round(motion_face, 4),
        "tipo_movimiento": "dinamica" if motion >= 0.18 else "estatica",
    }


def compatibility_penalty(actual: dict[str, Any], sample: dict[str, Any]) -> float:
    if not sample:
        return 0.25
    penalty = 0.0
    actual_hands = int(actual.get("manos_max", 0) or 0)
    sample_hands = int(sample.get("manos_max", 0) or 0)
    if actual_hands != sample_hands:
        penalty += abs(actual_hands - sample_hands) * 0.16
    if actual.get("tipo_movimiento") != sample.get("tipo_movimiento"):
        penalty += 0.06
    duration_a = max(1, int(actual.get("duracion_ms", 1) or 1))
    duration_b = max(1, int(sample.get("duracion_ms", 1) or 1))
    penalty += min(0.10, abs(math.log(duration_a / duration_b)) * 0.05)
    return penalty


def edge_distance(a: list[dict[str, Any]], b: list[dict[str, Any]]) -> float:
    if not a or not b:
        return 9.0
    return (frame_distance(a[0], b[0]) + frame_distance(a[-1], b[-1])) / 2


def classify(
    sequence: list[dict[str, Any]], samples: list[dict[str, Any]]
) -> dict[str, Any] | None:
    if not samples:
        return None

    analysis = analyze_sequence(sequence)
    current_descriptor = sequence_descriptor(sequence)
    current_trajectory = trajectory_descriptor(sequence)
    current_posture, current_dominant_hands = posture_descriptor(sequence)
    preselected = []
    hard_rejections = []
    totals: dict[str, int] = {}

    for raw_sample in samples:
        sample = prepare_model_sample(raw_sample)
        features = sample.get("features")
        if not isinstance(features, dict) or features.get("version") != MODEL_VERSION:
            continue
        code = str(sample.get("codigo") or "")
        aprendizaje = learning_metadata(sample, features)
        if aprendizaje.get("rol") == "rechazo":
            hard_rejections.append({
                "sample": sample,
                "features": features,
                "fast": vector_distance(
                    current_descriptor,
                    features.get("descriptor") or [],
                ),
            })
            continue
        declared_total = sample.get("muestras_totales", 0)
        try:
            declared_total = max(0, int(declared_total))
        except (TypeError, ValueError):
            declared_total = 0
        totals[code] = max(totals.get(code, 0) + 1, declared_total)
        sequence_fast = vector_distance(
            current_descriptor, features.get("descriptor") or []
        )
        posture_fast = posture_distance(
            current_posture,
            current_dominant_hands,
            features.get("postura_python") or [],
            int(features.get("manos_dominantes_python", 0) or 0),
        )
        fast = (posture_fast * 0.62) + (sequence_fast * 0.38)
        fast += compatibility_penalty(analysis, features.get("analisis") or {}) * 0.35
        preselected.append(
            {
                "sample": sample,
                "features": features,
                "fast": fast,
                "posture": posture_fast,
            }
        )

    if not preselected:
        return None

    preselected.sort(key=lambda item: item["fast"])
    per_class: dict[str, int] = {}
    filtered = []
    for candidate in preselected:
        code = candidate["sample"].get("codigo")
        if per_class.get(code, 0) >= MAX_DTW_SAMPLES_PER_CLASS:
            continue
        per_class[code] = per_class.get(code, 0) + 1
        filtered.append(candidate)
        if len(filtered) >= MAX_DTW_CANDIDATES:
            break

    results = []
    for candidate in filtered:
        sample = candidate["sample"]
        points = sample.get("puntos")
        if not isinstance(points, list) or not points:
            continue

        features = candidate["features"]
        dtw = dtw_distance(sequence, points)
        trajectory = vector_distance(
            current_trajectory,
            features.get("trayectoria") or trajectory_descriptor(points),
        )
        edges = edge_distance(sequence, points)
        compatibility = compatibility_penalty(analysis, features.get("analisis") or {})
        if analysis["tipo_movimiento"] == "estatica":
            # En letras y posturas quietas importan los dedos y la palma. La
            # entrada o retirada de la mano no debe convertir una A en B/C/D.
            distance = (
                (candidate["posture"] * 0.56)
                + (dtw * 0.20)
                + (trajectory * 0.08)
                + (edges * 0.05)
                + (candidate["fast"] * 0.11)
                + (compatibility * 0.16)
            )
        else:
            # Las palabras dinamicas conservan trayectoria, rotacion, rostro y
            # relacion con el cuerpo, con la postura como apoyo adicional.
            distance = (
                (dtw * 0.38)
                + (candidate["posture"] * 0.22)
                + (trajectory * 0.18)
                + (edges * 0.09)
                + (candidate["fast"] * 0.13)
                + (compatibility * 0.24)
            )
        results.append(
            {
                "codigo": sample.get("codigo"),
                "texto": sample.get("texto"),
                "distancia": distance,
            }
        )

    if not results:
        return None

    classes: dict[str, dict[str, Any]] = {}
    for result in results:
        code = result["codigo"]
        classes.setdefault(
            code, {"codigo": code, "texto": result["texto"], "distancias": []}
        )
        classes[code]["distancias"].append(result["distancia"])

    ranking = []
    for item in classes.values():
        distances = sorted(item["distancias"])
        best = distances[: min(5, len(distances))]
        base = best[: min(3, len(best))]
        robust_average = sum(base) / max(1, len(base))
        class_distance = (
            best[0]
            if item["codigo"] == NEGATIVE_CODE
            else (robust_average * 0.72) + (best[0] * 0.28)
        )
        ranking.append(
            {
                "codigo": item["codigo"],
                "texto": item["texto"],
                "distancia": class_distance,
                "muestras": totals.get(item["codigo"], len(distances)),
            }
        )

    ranking.sort(key=lambda item: item["distancia"])

    # Una correccion no solo crea un ejemplo positivo. Tambien deja una
    # contra-muestra para que el gesto corregido no vuelva a caer en la etiqueta
    # equivocada. Se calcula solo sobre los candidatos mas cercanos para cuidar
    # la velocidad cuando el diccionario crezca.
    hard_rejections.sort(key=lambda item: item["fast"])
    rejection_distances: dict[str, float] = {}
    for candidate in hard_rejections[:MAX_HARD_REJECTION_CANDIDATES]:
        if candidate["fast"] > 0.45:
            continue
        points = parse_json(candidate["sample"].get("puntos"), [])
        if not isinstance(points, list) or not points:
            continue
        sequence_rejection = vector_distance(
            current_descriptor,
            candidate["features"].get("descriptor") or [],
        )
        posture_rejection = posture_distance(
            current_posture,
            current_dominant_hands,
            candidate["features"].get("postura_python") or [],
            int(candidate["features"].get("manos_dominantes_python", 0) or 0),
        )
        distance = (posture_rejection * 0.68) + (sequence_rejection * 0.32)
        code = str(candidate["sample"].get("codigo") or "")
        rejection_distances[code] = min(
            rejection_distances.get(code, 9.0),
            distance,
        )

    # Una contra-muestra no puede borrar una clase por si sola: puede haber
    # correcciones antiguas o muy parecidas a una muestra positiva valida. Solo
    # penaliza la clase cuando el rechazo esta claramente mas cerca que su mejor
    # evidencia positiva. Asi, una coincidencia positiva exacta siempre gana.
    for item in ranking:
        rejection_distance = rejection_distances.get(item["codigo"], 9.0)
        positive_distance = item["distancia"]
        rejection_is_stronger = (
            rejection_distance <= HARD_REJECTION_DISTANCE
            and (rejection_distance + 0.045) < positive_distance
        )
        item["rechazo_penalizado"] = rejection_is_stronger
        if rejection_is_stronger:
            gap = positive_distance - rejection_distance
            item["distancia"] += min(0.34, 0.08 + (gap * 0.85))

    ranking.sort(key=lambda item: item["distancia"])

    negative = None
    ranking_without_negative = []
    for item in ranking:
        if item["codigo"] == NEGATIVE_CODE:
            negative = item
        else:
            ranking_without_negative.append(item)
    ranking = ranking_without_negative
    if not ranking:
        return None

    best = ranking[0]
    second = ranking[1] if len(ranking) > 1 else None
    threshold = 0.82 if analysis["tipo_movimiento"] == "dinamica" else 0.68
    intermittent_two_hands = (
        analysis["manos_max"] >= 2 and 0.40 <= analysis["ratio_dos_manos"] < 0.95
    )
    acceptance_limit = threshold * (0.88 if intermittent_two_hands else 0.74)
    minimum_confidence = 0.47 if intermittent_two_hands else MINIMUM_CONFIDENCE
    margin = (
        (second["distancia"] - best["distancia"])
        if second
        else (threshold - best["distancia"])
    )
    absolute_confidence = clamp(1 - (best["distancia"] / threshold))
    margin_confidence = clamp(margin / max(0.12, best["distancia"] * 0.45))
    evidence = clamp(
        math.log1p(best["muestras"]) / math.log1p(24),
    )
    confidence = clamp(
        (absolute_confidence * 0.60)
        + (margin_confidence * 0.30)
        + (analysis["calidad"] * 0.07)
        + (evidence * 0.03)
    )

    enough_samples = best["muestras"] >= MINIMUM_SAMPLES_PER_PHRASE
    strong_match = best["distancia"] <= acceptance_limit * 0.72
    enough_separation = (second is None) or margin >= 0.075 or strong_match
    looks_negative = bool(
        negative
        and negative["distancia"] <= acceptance_limit
        and negative["distancia"] <= (best["distancia"] + 0.015)
    )
    reinforced = (
        best["muestras"] >= 10
        and margin >= 0.08
        and best["distancia"] <= (acceptance_limit * 0.90)
        and analysis["calidad"] >= 0.80
    )
    enough_confidence = confidence >= minimum_confidence or (
        reinforced and confidence >= 0.64
    )
    accepted = (
        enough_samples
        and best["distancia"] <= acceptance_limit
        and enough_separation
        and enough_confidence
        and analysis["calidad"] >= 0.64
        and not looks_negative
    )

    if not enough_samples:
        return None

    alternatives = [
        {
            "codigo": item["codigo"],
            "texto": item["texto"],
            "confianza": round(clamp(1 - (item["distancia"] / threshold)), 3),
        }
        for item in ranking[:3]
    ]

    return {
        "codigo": best["codigo"] if accepted else None,
        "codigo_candidato": best["codigo"],
        "texto": best["texto"],
        "confianza": round(confidence, 3),
        "aceptada": accepted,
        "es_negativa": looks_negative,
        "alternativas": alternatives,
        "diagnostico": {
            "motor": "python",
            "distancia": round(best["distancia"], 4),
            "umbral": threshold,
            "limite_aceptacion": round(acceptance_limit, 4),
            "dos_manos_intermitentes": intermittent_two_hands,
            "margen": round(margin, 4),
            "muestras": best["muestras"],
            "tipo": analysis["tipo_movimiento"],
            "distancia_negativa": round(negative["distancia"], 4) if negative else None,
            "distancia_rechazo_aprendido": round(
                rejection_distances.get(best["codigo"], 9.0), 4
            ),
            "rechazo_penalizado": bool(best.get("rechazo_penalizado")),
            "evidencia_aprendida": round(evidence, 3),
            "evidencia_reforzada": reinforced,
            "negativo_calculado": looks_negative,
            "aceptado_calculado": accepted,
            "build": ENGINE_BUILD,
        },
    }


class RequestHandler(BaseHTTPRequestHandler):
    server_version = "MediSignSenasPython/1.0"

    def _send_json(self, status: int, payload: dict[str, Any]) -> None:
        body = json.dumps(payload, ensure_ascii=False).encode("utf-8")
        try:
            self.send_response(status)
            self.send_header("Content-Type", "application/json; charset=utf-8")
            self.send_header("Content-Length", str(len(body)))
            self.end_headers()
            self.wfile.write(body)
        except (BrokenPipeError, ConnectionAbortedError, ConnectionResetError):
            # Laravel puede cancelar una petición que ya superó su tiempo
            # límite. En ese caso el cliente cerró el socket y no hay nada que
            # responder; el servidor Python debe seguir atendiendo las demás.
            return

    def do_GET(self) -> None:
        if self.path == "/health":
            with MODEL_LOCK:
                samples_count = len(MODEL_SAMPLES)
                revision = MODEL_REVISION
            self._send_json(
                200,
                {
                    "ok": True,
                    "motor": "python",
                    "version_modelo": MODEL_VERSION,
                    "build": ENGINE_BUILD,
                    "muestras_modelo": samples_count,
                    "revision_modelo": revision,
                },
            )
            return
        self._send_json(404, {"ok": False, "message": "Ruta no encontrada."})

    def do_POST(self) -> None:
        if self.path != "/recognize":
            self._send_json(404, {"ok": False, "message": "Ruta no encontrada."})
            return

        try:
            length = int(self.headers.get("Content-Length", "0"))
            raw = self.rfile.read(length)
            payload = json.loads(raw.decode("utf-8"))
            sequence = payload.get("secuencia") or []
            incoming_samples = payload.get("muestras") or []
            revision = str(payload.get("revision_modelo") or "")
            update_model = bool(payload.get("actualizar_modelo"))
            if not isinstance(sequence, list) or not isinstance(incoming_samples, list):
                self._send_json(
                    422, {"ok": False, "message": "Formato de datos invalido."}
                )
                return

            global MODEL_REVISION, MODEL_SAMPLES
            with MODEL_LOCK:
                if update_model:
                    MODEL_SAMPLES = prepare_model_samples(incoming_samples)
                    MODEL_REVISION = revision
                elif MODEL_REVISION != revision:
                    self._send_json(
                        409,
                        {
                            "ok": False,
                            "requiere_modelo": True,
                            "message": "El diccionario debe sincronizarse.",
                        },
                    )
                    return
                samples = list(MODEL_SAMPLES)

            started = time.perf_counter()
            prediction = classify(sequence, samples)
            elapsed_ms = (time.perf_counter() - started) * 1000
            self._send_json(
                200,
                {
                    "ok": True,
                    "prediccion": prediction,
                    "motor": "python",
                    "muestras_modelo": len(samples),
                    "tiempo_motor_ms": round(elapsed_ms, 1),
                },
            )
        except (BrokenPipeError, ConnectionAbortedError, ConnectionResetError):
            return
        except Exception as exc:
            self._send_json(500, {"ok": False, "message": str(exc)})

    def log_message(self, format: str, *args: Any) -> None:
        print("[senas-python]", format % args)


def main() -> None:
    host = os.getenv("SENAS_PYTHON_HOST", "127.0.0.1")
    port = int(os.getenv("SENAS_PYTHON_PORT", "5055"))
    server = ThreadingHTTPServer((host, port), RequestHandler)
    print(f"MediSign senas Python escuchando en http://{host}:{port}")
    print("Health check: http://127.0.0.1:5055/health")
    server.serve_forever()


if __name__ == "__main__":
    main()
