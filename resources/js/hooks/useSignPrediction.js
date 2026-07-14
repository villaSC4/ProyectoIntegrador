import { useState, useEffect, useRef, useCallback } from "react";
import * as tf from "@tensorflow/tfjs";

const CLASES_MEDISIGN = [
  "cabeza", "cara", "corazon", "dias", "doctor", "dolor", "escalofrios", 
  "estomago", "fiebre", "garganta", "gracias", "granitos", "gripe", 
  "hola", "mal", "mareo", "mucho", "nauseas", "tengo", "tos"
];

const DICCIONARIO_MEDICO = {
  "hola,doctor": "Hola doctor, buenas tardes.",
  "gracias,doctor": "Muchas gracias por la atención, doctor.",
  "tengo,dolor,cabeza": "El paciente presenta un fuerte dolor de cabeza.",
  "tengo,dolor,estomago": "El paciente manifiesta dolor estomacal agudo.",
  "tengo,dolor,garganta": "El paciente refiere dolor e irritación de garganta.",
  "tengo,dolor,corazon": "El paciente reporta dolor u opresión en la zona del pecho.",
  "tengo,mucho,dolor": "El paciente expresa sentir un dolor de alta intensidad.",
  "dolor,mucho": "El paciente manifiesta un dolor muy severo.",
  "mal,mucho": "El paciente indica que se siente extremadamente mal.",
  "mucho,mal": "El paciente se encuentra en un estado de gran malestar general.",
  "tengo,fiebre": "El paciente reporta un cuadro febril.",
  "tengo,fiebre,dias": "El paciente lleva varios días con fiebre persistente.",
  "tengo,gripe": "El paciente presenta síntomas gripales.",
  "tengo,gripe,dias": "El paciente indica tener síntomas de gripe por varios días.",
  "tengo,nauseas": "El paciente refiere sensación de náuseas.",
  "tengo,mareo": "El paciente experimenta episodios de mareo.",
  "tengo,nauseas,mareo": "El paciente experimenta náuseas constantes acompañadas de mareos.",
  "tengo,mareo,nauseas": "El paciente presenta mareos severos que le provocan náuseas.",
  "tengo,fiebre,escalofrios": "El paciente presenta un cuadro de fiebre con escalofríos.",
  "tengo,escalofrios": "El paciente experimenta episodios de escalofríos.",
  "tengo,tos": "El paciente presenta tos constante.",
  "tengo,tos,mucho": "El paciente sufre de ataques de tos severa y frecuente.",
  "tengo,tos,garganta": "El paciente sufre de tos constante e irritación en la garganta.",
  "tengo,granitos": "El paciente reporta la aparición de erupciones o granitos.",
  "tengo,granitos,cara": "El paciente presenta una erupción cutánea o granitos en el rostro.",
  "dolor,cabeza,mucho": "El paciente sufre de una cefalea de muy alta intensidad.",
  "dolor,estomago,mucho": "El paciente padece de dolor abdominal intenso.",
  "dolor,corazon,mucho": "El paciente refiere un fuerte dolor punzante en el pecho.",
  "dolor,garganta,mucho": "El paciente presenta un dolor agudo e irritación severa en la garganta.",
  "tengo,mal,estomago": "El paciente indica fuerte malestar estomacal o indigestión.",
  "tengo,mal,cabeza": "El paciente experimenta gran malestar en la zona de la cabeza.",
  "tengo,mal,corazon": "El paciente siente un malestar anómalo en la zona cardíaca.",
  "tengo,dias,mal": "El paciente lleva varios días sintiéndose muy mal de salud.",
  "doctor,tengo,dolor": "Doctor, el paciente ingresa reportando dolor agudo.",
  "doctor,tengo,fiebre": "Doctor, el paciente ingresa reportando cuadro febril.",
  "doctor,mucho,dolor": "Doctor, el paciente requiere atención por dolor extremo.",
  "tengo,garganta,mal": "El paciente refiere sentir la garganta en muy mal estado.",
  "tengo,cara,mal": "El paciente siente malestar general reflejado en el rostro."
};

function normalizarMano(landmarks) {
  const wrist = landmarks[0];
  const dxScale = landmarks[9].x - wrist.x;
  const dyScale = landmarks[9].y - wrist.y;
  const dzScale = landmarks[9].z - wrist.z;
  let scale = Math.sqrt(dxScale ** 2 + dyScale ** 2 + dzScale ** 2);
  
  if (scale < 0.001) scale = 1.0;

  const coordsNormalizadas = [];
  landmarks.forEach((lm) => {
    coordsNormalizadas.push((lm.x - wrist.x) / scale);
    coordsNormalizadas.push((lm.y - wrist.y) / scale);
    coordsNormalizadas.push((lm.z - wrist.z) / scale);
  });

  return coordsNormalizadas;
}

export function useSignPrediction(handData) {
  const [confidenceThreshold, setConfidenceThresholdState] = useState(0.94);
  const [cooldownMs, setCooldownMsState] = useState(1800);
  const [stabilizationFrames, setStabilizationFramesState] = useState(6);
  const [stabilizationThreshold, setStabilizationThresholdState] = useState(4);

  const [activePrediction, setActivePrediction] = useState(null);
  const [activeConfidence, setActiveConfidence] = useState(0);
  const [alternatives, setAlternatives] = useState([]);
  
  const [isLocked, setIsLocked] = useState(false);

  const [wordsSequence, setWordsSequence] = useState([]);
  const [sentence, setSentence] = useState("Esperando señas...");
  const [history, setHistory] = useState([]);
  const [isPaused, setIsPaused] = useState(false);

  const modelRef = useRef(null);
  const bufferRef = useRef([]); 
  const lastAppendedCharRef = useRef(""); 
  const lastAppendTimeRef = useRef(0); 

  const isLockedRef = useRef(false);
  const lockTimeoutRef = useRef(null);

  const confidenceThresholdRef = useRef(confidenceThreshold);
  const cooldownMsRef = useRef(cooldownMs);
  const stabilizationFramesRef = useRef(stabilizationFrames);
  const stabilizationThresholdRef = useRef(stabilizationThreshold);

  useEffect(() => { confidenceThresholdRef.current = confidenceThreshold; }, [confidenceThreshold]);
  useEffect(() => { cooldownMsRef.current = cooldownMs; }, [cooldownMs]);
  useEffect(() => { stabilizationFramesRef.current = stabilizationFrames; }, [stabilizationFrames]);
  useEffect(() => { stabilizationThresholdRef.current = stabilizationThreshold; }, [stabilizationThreshold]);

  useEffect(() => {
    async function loadSignModel() {
      try {
        const loadedModel = await tf.loadLayersModel("/model/model.json");
        modelRef.current = loadedModel;
        console.log("🚀 Red Neuronal de MediSign-ID cargada en el cargador de predicciones.");
      } catch (err) {
        console.error("❌ Error al cargar model.json en useSignPrediction:", err);
      }
    }
    loadSignModel();
  }, []);

  const unlockSystem = useCallback(() => {
    if (lockTimeoutRef.current) {
      clearTimeout(lockTimeoutRef.current);
    }
    isLockedRef.current = false;
    setIsLocked(false);
    setAlternatives([]);
    bufferRef.current = [];
  }, []);

  const clearSentence = useCallback(() => {
    setWordsSequence([]);
    setSentence("Esperando señas...");
    lastAppendedCharRef.current = "";
    unlockSystem();
  }, [unlockSystem]);

  const deleteLastChar = useCallback(() => {
    setWordsSequence((prev) => {
      const nuevo = prev.slice(0, -1);
      actualizarTextoTraduccion(nuevo);
      return nuevo;
    });
  }, []);

  const saveSentenceToHistory = useCallback(() => {
    if (wordsSequence.length > 0) {
      setHistory((prev) => [sentence, ...prev].slice(0, 20)); 
      clearSentence();
    }
  }, [wordsSequence, sentence, clearSentence]);

  const actualizarTextoTraduccion = (listaPalabras) => {
    if (listaPalabras.length === 0) {
      setSentence("Esperando señas...");
      return;
    }

    for (let tamano of [3, 2]) {
      if (listaPalabras.length >= tamano) {
        const subGrupo = listaPalabras.slice(-tamano).join(",");
        if (DICCIONARIO_MEDICO[subGrupo]) {
          setSentence(DICCIONARIO_MEDICO[subGrupo]);
          return;
        }
      }
    }

    setSentence(listaPalabras.map((p) => p.toUpperCase()).join(" ➔ "));
  };

  const appendCharacter = useCallback((label) => {
    setWordsSequence((prev) => {
      if (prev.length > 0 && prev[prev.length - 1] === label) {
        return prev;
      }
      const nuevaSecuencia = [...prev, label];
      actualizarTextoTraduccion(nuevaSecuencia);
      return nuevaSecuencia;
    });
    unlockSystem();
  }, [unlockSystem]);

  const skipPrediction = useCallback(() => {
    unlockSystem();
  }, [unlockSystem]);

  const setConfidenceThreshold = useCallback((val) => setConfidenceThresholdState(val), []);
  const setCooldownMs = useCallback((val) => setCooldownMsState(val), []);
  const setStabilizationFrames = useCallback((val) => setStabilizationFramesState(val), []);
  const setStabilizationThreshold = useCallback((val) => setStabilizationThresholdState(val), []);

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
        setAlternatives([]);
      }, 0);
      bufferRef.current = [];
      return;
    }

    if (isLockedRef.current) return;

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

    const coordsNormalizadas = normalizarMano(hand.landmarks);

    if (coordsNormalizadas.length === 63 && modelRef.current) {
      try {
        tf.tidy(() => {
          const inputTensor = tf.tensor2d([coordsNormalizadas], [1, 63]);
          const outputTensor = modelRef.current.predict(inputTensor);
          const probabilities = Array.from(outputTensor.dataSync());

          const mapeoClases = probabilities.map((prob, idx) => ({
            label: CLASES_MEDISIGN[idx],
            confidence: prob
          }));

          mapeoClases.sort((a, b) => b.confidence - a.confidence);

          const mejorOpcion = mapeoClases[0];

          if (mejorOpcion.confidence >= confidenceThresholdRef.current) {
            updateBuffer(mejorOpcion.label, mejorOpcion.confidence);
          } else if (mejorOpcion.confidence >= 0.35) {
            isLockedRef.current = true;
            setIsLocked(true);
            updateBuffer("nothing", mejorOpcion.confidence);
            
            const topSugerencias = mapeoClases.slice(0, 3).map(alt => ({
              label: alt.label,
              percent: Math.round(alt.confidence * 100)
            }));
            
            setAlternatives(topSugerencias);

            if (lockTimeoutRef.current) clearTimeout(lockTimeoutRef.current);
            lockTimeoutRef.current = setTimeout(() => {
              unlockSystem();
            }, 5000);

          } else {
            updateBuffer("nothing", 0);
          }
        });
      } catch (err) {
        console.error("Error al predecir en useSignPrediction:", err);
      }
    }

  }, [handData, isPaused, appendCharacter, unlockSystem]);

  useEffect(() => {
    return () => {
      if (lockTimeoutRef.current) clearTimeout(lockTimeoutRef.current);
    };
  }, []);

  return {
    activePrediction,
    activeConfidence,
    alternatives,
    isLocked, 
    sentence, 
    wordsSequence, 
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
    appendCharacter,
    skipPrediction, 
  };
}