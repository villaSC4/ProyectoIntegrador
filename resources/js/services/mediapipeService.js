import {
  HandLandmarker,
  FilesetResolver,
} from "@mediapipe/tasks-vision";


const WASM_PATH =
  "https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@latest/wasm";

const MODEL_PATH =
  "https://storage.googleapis.com/mediapipe-models/hand_landmarker/hand_landmarker/float16/1/hand_landmarker.task";

// 21 landmark connections as defined by MediaPipe Hand topology
export const HAND_CONNECTIONS = [
  [0, 1], [1, 2], [2, 3], [3, 4],           // Thumb
  [0, 5], [5, 6], [6, 7], [7, 8],           // Index finger
  [0, 9], [9, 10], [10, 11], [11, 12],      // Middle finger
  [0, 13], [13, 14], [14, 15], [15, 16],    // Ring finger
  [0, 17], [17, 18], [18, 19], [19, 20],    // Pinky
  [5, 9], [9, 13], [13, 17],                // Palm connections
];

export const LANDMARK_NAMES = [
  "Muñeca",
  "CMC Pulgar", "MCP Pulgar", "IP Pulgar", "Punta Pulgar",
  "MCP Índice", "PIP Índice", "DIP Índice", "Punta Índice",
  "MCP Medio", "PIP Medio", "DIP Medio", "Punta Medio",
  "MCP Anular", "PIP Anular", "DIP Anular", "Punta Anular",
  "MCP Meñique", "PIP Meñique", "DIP Meñique", "Punta Meñique",
];

// ─── Service ──────────────────────────────────────────────────────────────────

let handLandmarkerInstance = null;
let isInitializing = false;

/**
 * Creates and initializes a HandLandmarker instance.
 * Returns a cached instance if already initialized.
 */
export async function initializeHandLandmarker() {
  if (handLandmarkerInstance) return handLandmarkerInstance;
  if (isInitializing) {
    // Wait until the current initialization completes
    return new Promise((resolve, reject) => {
      const interval = setInterval(() => {
        if (handLandmarkerInstance) {
          clearInterval(interval);
          resolve(handLandmarkerInstance);
        }
      }, 100);
      setTimeout(() => {
        clearInterval(interval);
        reject(new Error("Timeout esperando inicialización de MediaPipe"));
      }, 30000);
    });
  }

  isInitializing = true;

  try {
    const vision = await FilesetResolver.forVisionTasks(WASM_PATH);

    handLandmarkerInstance = await HandLandmarker.createFromOptions(vision, {
      baseOptions: {
        modelAssetPath: MODEL_PATH,
        delegate: "GPU",
      },
      runningMode: "VIDEO",
      numHands: 2,
      minHandDetectionConfidence: 0.8,
      minHandPresenceConfidence: 0.8,
      minTrackingConfidence: 0.8,
    });

    return handLandmarkerInstance;
  } catch (error) {
    console.warn("GPU delegate no disponible, usando CPU:", error.message);
    const vision = await FilesetResolver.forVisionTasks(WASM_PATH);
    handLandmarkerInstance = await HandLandmarker.createFromOptions(vision, {
      baseOptions: {
        modelAssetPath: MODEL_PATH,
        delegate: "CPU",
      },
      runningMode: "VIDEO",
      numHands: 2,
      minHandDetectionConfidence: 0.8,
      minHandPresenceConfidence: 0.8,
      minTrackingConfidence: 0.8,
    });
    return handLandmarkerInstance;
  } finally {
    isInitializing = false;
  }
}

/**
 * Runs hand detection on a video frame.
 * @param {HTMLVideoElement} videoElement
 * @param {number} timestamp - performance.now() timestamp
 * @returns {HandLandmarkerResult} result object
 */
export function detectHands(videoElement, timestamp) {
  if (!handLandmarkerInstance) {
    throw new Error("HandLandmarker no inicializado. Llama initializeHandLandmarker() primero.");
  }
  return handLandmarkerInstance.detectForVideo(videoElement, timestamp);
}

export function destroyHandLandmarker() {
  if (handLandmarkerInstance) {
    handLandmarkerInstance.close();
    handLandmarkerInstance = null;
  }
}