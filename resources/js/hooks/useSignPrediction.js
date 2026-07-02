
import { useState, useEffect, useRef, useCallback } from "react";
import { predictSign } from "../services/modelInferenceService";

export function useSignPrediction(handData) {
  const [confidenceThreshold, setConfidenceThresholdState] = useState(0.80);
  const [cooldownMs, setCooldownMsState] = useState(1500); 
  const [stabilizationFrames, setStabilizationFramesState] = useState(5);
  const [stabilizationThreshold, setStabilizationThresholdState] = useState(4);

  const [activePrediction, setActivePrediction] = useState(null);
  const [activeConfidence, setActiveConfidence] = useState(0);
  const [sentence, setSentence] = useState("");
  const [history, setHistory] = useState([]);
  const [isPaused, setIsPaused] = useState(false);

  const bufferRef = useRef([]); 
  const lastAppendedCharRef = useRef(""); 
  const lastAppendTimeRef = useRef(0); 

  const confidenceThresholdRef = useRef(confidenceThreshold);
  const cooldownMsRef = useRef(cooldownMs);
  const stabilizationFramesRef = useRef(stabilizationFrames);
  const stabilizationThresholdRef = useRef(stabilizationThreshold);

  useEffect(() => { confidenceThresholdRef.current = confidenceThreshold; }, [confidenceThreshold]);
  useEffect(() => { cooldownMsRef.current = cooldownMs; }, [cooldownMs]);
  useEffect(() => { stabilizationFramesRef.current = stabilizationFrames; }, [stabilizationFrames]);
  useEffect(() => { stabilizationThresholdRef.current = stabilizationThreshold; }, [stabilizationThreshold]);

  const clearSentence = useCallback(() => {
    setSentence("");
    lastAppendedCharRef.current = "";
  }, []);

  const deleteLastChar = useCallback(() => {
    setSentence((prev) => prev.slice(0, -1));
  }, []);

  const addSpace = useCallback(() => {
    setSentence((prev) => prev + " ");
  }, []);

  const saveSentenceToHistory = useCallback(() => {
    if (sentence.trim()) {
      setHistory((prev) => [sentence.trim(), ...prev].slice(0, 20)); 
      setSentence("");
      lastAppendedCharRef.current = "";
    }
  }, [sentence]);

  const setConfidenceThreshold = useCallback((val) => setConfidenceThresholdState(val), []);
  const setCooldownMs = useCallback((val) => setCooldownMsState(val), []);
  const setStabilizationFrames = useCallback((val) => setStabilizationFramesState(val), []);
  const setStabilizationThreshold = useCallback((val) => setStabilizationThresholdState(val), []);

  const appendCharacter = useCallback((label) => {
    setSentence((prev) => {
      if (label === "space") {
        return prev.endsWith(" ") ? prev : prev + " ";
      } else if (label === "del") {
        return prev.slice(0, -1);
      } else {
        return prev + label;
      }
    });
  }, []);

  useEffect(() => {
    function updateBuffer(label, confidence) {
      const buffer = [...bufferRef.current, label];
      if (buffer.length > stabilizationFramesRef.current) {
        buffer.shift();
      }
      bufferRef.current = buffer;

      const counts = {};
      let stableLabel = "nothing";
      let stableCount = 0;

      buffer.forEach((item) => {
        counts[item] = (counts[item] || 0) + 1;
        if (counts[item] > stableCount) {
          stableCount = counts[item];
          stableLabel = item;
        }
      });

      if (stableCount >= stabilizationThresholdRef.current && stableLabel !== "nothing") {
        setTimeout(() => {
          setActivePrediction(stableLabel);
          setActiveConfidence(confidence);
        }, 0);

        const now = performance.now();
        const timeSinceLastAppend = now - lastAppendTimeRef.current;
        const isDifferentChar = stableLabel !== lastAppendedCharRef.current;

        if (isDifferentChar || timeSinceLastAppend >= cooldownMsRef.current) {
          setTimeout(() => appendCharacter(stableLabel), 0);
          lastAppendedCharRef.current = stableLabel;
          lastAppendTimeRef.current = now;
        }
      } else {
        setTimeout(() => {
          setActivePrediction(null);
          setActiveConfidence(0);
        }, 0);

        if (stableLabel === "nothing" || stableCount < 2) {
          lastAppendedCharRef.current = "";
        }
      }
    }

    if (isPaused) {
      setTimeout(() => {
        setActivePrediction(null);
        setActiveConfidence(0);
      }, 0);
      bufferRef.current = [];
      return;
    }

    if (!handData || handData.length === 0) {
      updateBuffer("nothing", 0);
      setTimeout(() => {
        setActivePrediction(null);
        setActiveConfidence(0);
      }, 0);
      return;
    }

    const hand = handData[0];
    if (!hand || !hand.landmarks || hand.landmarks.length < 21) return;

    const yCoordinates = hand.landmarks.map((lm) => lm.y);
    const yMin = Math.min(...yCoordinates);
    const yMax = Math.max(...yCoordinates);
    const currentHeight = yMax - yMin;

    if (currentHeight < 0.01) return;

    const TARGET_X0 = 0.5437777321708132;
    const TARGET_Y0 = 0.7096867116457469;
    const TARGET_HEIGHT = 0.42379087093637013;

    const scale = TARGET_HEIGHT / currentHeight;
    const width = hand.videoWidth || 640;
    const height = hand.videoHeight || 480;
    const aspect = width / height;

    const flatLandmarks = [];
    const isRightHand = hand.handedness === "Right";
    const wrist = hand.landmarks[0];

    hand.landmarks.forEach((lm) => {
      const dx = lm.x - wrist.x;
      const dy = lm.y - wrist.y;
      const dxMirrored = isRightHand ? -dx : dx;

      const xNorm = TARGET_X0 + dxMirrored * aspect * scale;
      const yNorm = TARGET_Y0 + dy * scale;
      const zNorm = lm.z * scale;

      flatLandmarks.push(xNorm, yNorm, zNorm);
    });

    let isCurrentFrame = true;
    predictSign(flatLandmarks)
      .then((result) => {
        if (!isCurrentFrame) return;
        const { label, confidence } = result;

        if (confidence >= confidenceThresholdRef.current) {
          updateBuffer(label, confidence);
        } else {
          updateBuffer("nothing", confidence);
        }
      })
      .catch((error) => {
        console.error("Error en la inferencia del modelo:", error);
      });

    return () => {
      isCurrentFrame = false;
    };
  }, [handData, isPaused, appendCharacter]);

  return {
    activePrediction,
    activeConfidence,
    sentence,
    history,
    isPaused,
    confidenceThreshold,
    cooldownMs,
    stabilizationFrames,
    stabilizationThreshold,
    setSentence,
    setHistory,
    setIsPaused,
    setConfidenceThreshold,
    setCooldownMs,
    setStabilizationFrames,
    setStabilizationThreshold,
    clearSentence,
    saveSentenceToHistory,
    deleteLastChar,
    addSpace,
  };
}