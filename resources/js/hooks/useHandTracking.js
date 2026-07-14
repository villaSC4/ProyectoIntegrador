import { useRef, useState, useEffect, useCallback } from "react";
import * as tf from "@tensorflow/tfjs";
import {
  initializeHandLandmarker,
  detectHands,
  destroyHandLandmarker,
} from "../services/mediapipeService"; 

export const TrackingStatus = {
  IDLE: "idle",
  LOADING: "loading",
  REQUESTING_CAMERA: "requesting_camera",
  ACTIVE: "active",
  ERROR: "error",
  PERMISSION_DENIED: "permission_denied",
};

const CLASES_MEDISIGN = [
  "cabeza", "cara", "corazon", "dias", "doctor", "dolor", "escalofrios", 
  "estomago", "fiebre", "garganta", "gracias", "granitos", "gripe", 
  "hola", "mal", "mareo", "mucho", "nauseas", "tengo", "tos"
];

function normalizarMano(landmarks) {
  const wrist = landmarks[0]; 

  const dxScale = landmarks[9].x - wrist.x;
  const dyScale = landmarks[9].y - wrist.y;
  const dzScale = landmarks[9].z - wrist.z;
  let scale = Math.sqrt(dxScale ** 2 + dyScale ** 2 + dzScale ** 2);
  
  if (scale < 0.001) {
    scale = 1.0;
  }

  const coordsNormalizadas = [];
  landmarks.forEach((lm) => {
    coordsNormalizadas.push((lm.x - wrist.x) / scale);
    coordsNormalizadas.push((lm.y - wrist.y) / scale);
    coordsNormalizadas.push((lm.z - wrist.z) / scale);
  });

  return coordsNormalizadas; 
}

function drawConnections(ctx, landmarks, width, height, color) {
  const CONNECTIONS = [
    [0,1],[1,2],[2,3],[3,4],
    [0,5],[5,6],[6,7],[7,8],
    [0,9],[9,10],[10,11],[11,12],
    [0,13],[13,14],[14,15],[15,16],
    [0,17],[17,18],[18,19],[19,20],
    [5,9],[9,13],[13,17],
  ];

  ctx.strokeStyle = color;
  ctx.lineWidth = 2.5;
  ctx.shadowColor = color;
  ctx.shadowBlur = 6;

  CONNECTIONS.forEach(([a, b]) => {
    const lmA = landmarks[a];
    const lmB = landmarks[b];
    if (!lmA || !lmB) return;
    ctx.beginPath();
    ctx.moveTo(lmA.x * width, lmA.y * height);
    ctx.lineTo(lmB.x * width, lmB.y * height);
    ctx.stroke();
  });

  ctx.shadowBlur = 0;
}

function drawLandmarks(ctx, landmarks, width, height, dotColor, accentColor) {
  landmarks.forEach((lm, i) => {
    const px = lm.x * width;
    const py = lm.y * height;

    ctx.beginPath();
    ctx.arc(px, py, 7, 0, Math.PI * 2);
    ctx.fillStyle = `${dotColor}33`;
    ctx.fill();

    ctx.beginPath();
    ctx.arc(px, py, 4, 0, Math.PI * 2);
    ctx.fillStyle = dotColor;
    ctx.fill();

    ctx.strokeStyle = "#ffffff";
    ctx.lineWidth = 1.5;
    ctx.stroke();

    ctx.fillStyle = accentColor;
    ctx.font = "bold 9px Inter, sans-serif";
    ctx.fillText(i, px + 6, py - 5);
  });
}

function drawResults(canvas, result, width, height) {
  const ctx = canvas.getContext("2d");
  ctx.clearRect(0, 0, width, height);

  const landmarks = result.landmarks ?? [];
  const handednesses = result.handednesses ?? [];

  landmarks.forEach((hand, handIdx) => {
    const label = handednesses[handIdx]?.[0]?.displayName ?? "Hand";
    const isLeft = label === "Left";

    const primaryColor = isLeft ? "#3b82f6" : "#a855f7";   
    const accentColor  = isLeft ? "#93c5fd" : "#d8b4fe";
    const dotColor     = isLeft ? "#60a5fa" : "#c084fc";

    drawConnections(ctx, hand, width, height, primaryColor);
    drawLandmarks(ctx, hand, width, height, dotColor, accentColor, handIdx);
  });
}

export function useHandTracking() {
  const videoRef = useRef(null);
  const canvasRef = useRef(null);
  const streamRef = useRef(null);
  const animationRef = useRef(null);
  const landmarkerRef = useRef(null);
  const lastTimestampRef = useRef(0);
  
  const fpsCounterRef = useRef({ frames: 0, lastTime: 0 });
  const runDetectionLoopRef = useRef();

  const tfModelRef = useRef(null);

  const [status, setStatus] = useState(TrackingStatus.IDLE);
  const [errorMessage, setErrorMessage] = useState(null);
  const [handData, setHandData] = useState([]);
  const [fps, setFps] = useState(0);

  const [prediction, setPrediction] = useState("Ninguna");
  const [probability, setProbability] = useState(0);

  const statusRef = useRef(status);
  useEffect(() => { statusRef.current = status; }, [status]);

  useEffect(() => {
    async function loadSignModel() {
      try {
        const loadedModel = await tf.loadLayersModel("/model/model.json");
        tfModelRef.current = loadedModel;
        console.log("🚀 Modelo de MediSign-ID cargado exitosamente en React.");
      } catch (err) {
        console.error("❌ Error al cargar el modelo TFJS:", err);
      }
    }
    loadSignModel();
  }, []);

  const predecirSena = async (landmarks) => {
    if (!tfModelRef.current) return;

    const coordsNormalizadas = normalizarMano(landmarks);

    tf.tidy(() => {
      const inputTensor = tf.tensor2d([coordsNormalizadas], [1, 63]);
      const outputTensor = tfModelRef.current.predict(inputTensor);
      const probabilities = outputTensor.dataSync(); 

      let maxIdx = 0;
      let maxVal = 0;
      for (let i = 0; i < probabilities.length; i++) {
        if (probabilities[i] > maxVal) {
          maxVal = probabilities[i];
          maxIdx = i;
        }
      }

      if (maxVal > 0.82) {
        setPrediction(CLASES_MEDISIGN[maxIdx]);
        setProbability(Math.round(maxVal * 100));
      } else {
        setPrediction("Alineando...");
        setProbability(0);
      }
    });
  };

  function runDetectionLoop() {
    const video = videoRef.current;
    const canvas = canvasRef.current;
    
    if (!video || !canvas || !landmarkerRef.current) {
      if (statusRef.current === TrackingStatus.ACTIVE) {
        animationRef.current = requestAnimationFrame(runDetectionLoopRef.current);
      }
      return;
    }

    if (video.readyState < 2) {
      animationRef.current = requestAnimationFrame(runDetectionLoopRef.current);
      return;
    }

    const now = performance.now();

    if (now === lastTimestampRef.current) {
      animationRef.current = requestAnimationFrame(runDetectionLoopRef.current);
      return;
    }
    lastTimestampRef.current = now;

    try {
      const result = detectHands(video, now);

      if (
        canvas.width !== video.videoWidth ||
        canvas.height !== video.videoHeight
      ) {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
      }

      const hands = (result.landmarks || []).map((landmarks, i) => ({
        index: i,
        handedness: result.handednesses?.[i]?.[0]?.displayName ?? "Unknown",
        confidence: result.handednesses?.[i]?.[0]?.score ?? 0,
        landmarks: landmarks.map((lm, j) => ({ id: j, x: lm.x, y: lm.y, z: lm.z })),
        worldLandmarks: result.worldLandmarks?.[i] ?? [],
        videoWidth: video.videoWidth,
        videoHeight: video.videoHeight,
      }));

      setHandData(hands);
      drawResults(canvas, result, video.videoWidth, video.videoHeight);

      if (result.landmarks && result.landmarks.length > 0) {
        predecirSena(result.landmarks[0]);
      } else {
        setPrediction("Ninguna");
        setProbability(0);
      }

      fpsCounterRef.current.frames++;
      
      if (fpsCounterRef.current.lastTime === 0) {
        fpsCounterRef.current.lastTime = now;
      }
      
      const elapsed = now - fpsCounterRef.current.lastTime;
      if (elapsed >= 1000) {
        setFps(Math.round((fpsCounterRef.current.frames * 1000) / elapsed));
        fpsCounterRef.current.frames = 0;
        fpsCounterRef.current.lastTime = now;
      }
    } catch (err) {
      if (err.message && err.message.includes("not initialized")) {
      } else {
        console.error("Error en detección:", err);
      }
    }

    if (statusRef.current === TrackingStatus.ACTIVE) {
      animationRef.current = requestAnimationFrame(runDetectionLoopRef.current);
    }
  }

  runDetectionLoopRef.current = runDetectionLoop;

  const startTracking = useCallback(async () => {
    setStatus(TrackingStatus.LOADING);
    setErrorMessage(null);

    try {
      landmarkerRef.current = await initializeHandLandmarker();

      setStatus(TrackingStatus.REQUESTING_CAMERA);
      const stream = await navigator.mediaDevices.getUserMedia({
        video: { width: { ideal: 1280 }, height: { ideal: 720 }, facingMode: "user" },
        audio: false,
      });

      streamRef.current = stream;
      const video = videoRef.current;
      if (!video) throw new Error("Elemento de video no disponible.");
      video.srcObject = stream;
      await video.play();

      setStatus(TrackingStatus.ACTIVE);
      statusRef.current = TrackingStatus.ACTIVE;
      animationRef.current = requestAnimationFrame(runDetectionLoopRef.current);
    } catch (error) {
      if (error.name === "NotAllowedError" || error.name === "PermissionDeniedError") {
        setStatus(TrackingStatus.PERMISSION_DENIED);
        setErrorMessage("Permiso de cámara denegado. Por favor, permítelo en el navegador.");
      } else {
        setStatus(TrackingStatus.ERROR);
        setErrorMessage(`Error al inicializar: ${error.message}`);
      }
    }
  }, []);

  const stopTracking = useCallback(() => {
    setStatus(TrackingStatus.IDLE);
    statusRef.current = TrackingStatus.IDLE;
    cancelAnimationFrame(animationRef.current);
    streamRef.current?.getTracks().forEach((t) => t.stop());
    streamRef.current = null;
    setHandData([]);
    setPrediction("Ninguna");
    setProbability(0);
  }, []);

  useEffect(() => {
    return () => {
      stopTracking();
      destroyHandLandmarker();
    };
  }, [stopTracking]);

  return {
    videoRef,
    canvasRef,
    status,
    errorMessage,
    handData,
    fps,
    prediction,     
    probability,  
    startTracking,
    stopTracking,
  };
}