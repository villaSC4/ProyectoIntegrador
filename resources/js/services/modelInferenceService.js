import * as tf from "@tensorflow/tfjs";

// Cached model instance
let loadedModel = null;
let loadPromise = null;

export const MODEL_CLASSES = [
  "cabeza", "cara", "corazon", "dias", "doctor", "dolor", "escalofrios", 
  "estomago", "fiebre", "garganta", "gracias", "granitos", "gripe", 
  "hola", "mal", "mareo", "mucho", "nauseas", "tengo", "tos"
];

/**
 * Loads the TensorFlow.js model from the public directory.
 * Implements a singleton promise pattern to avoid redundant loading calls.
 * @returns {Promise<tf.LayersModel>}
 */
export function loadModel() {
  if (loadedModel) {
    return Promise.resolve(loadedModel);
  }
  if (loadPromise) {
    return loadPromise;
  }

  loadPromise = tf.loadLayersModel("/model/model.json")
    .then((model) => {
      loadedModel = model;
      loadPromise = null;
      console.log("✅ Modelo MediSign-ID de TensorFlow.js cargado con éxito.");
      return model;
    })
    .catch((error) => {
      loadPromise = null;
      console.error("❌ Error al cargar el modelo de TensorFlow.js:", error);
      throw error;
    });

  return loadPromise;
}

/**
 * Performs inference on a flattened 63-element landmark vector.
 * @param {number[]} flatLandmarks - Array of 63 coordinates [x0, y0, z0, ..., x20, y20, z20]
 * @returns {Promise<{ label: string, confidence: number, probabilities: number[] }>}
 */
export async function predictSign(flatLandmarks) {
  if (flatLandmarks.length !== 63) {
    throw new Error(`Vector de entrada incorrecto: esperado 63 valores, recibido ${flatLandmarks.length}`);
  }

  const model = await loadModel();

  return tf.tidy(() => {
    const inputTensor = tf.tensor2d([flatLandmarks], [1, 63]);

    const predictionTensor = model.predict(inputTensor);

    const probabilities = Array.from(predictionTensor.dataSync());

    const maxIndex = probabilities.reduce((maxIdx, currentVal, currentIdx, arr) => 
      currentVal > arr[maxIdx] ? currentIdx : maxIdx, 0
    );
    const confidence = probabilities[maxIndex];
    const label = MODEL_CLASSES[maxIndex];

    return {
      label,
      confidence,
      probabilities,
    };
  });
}