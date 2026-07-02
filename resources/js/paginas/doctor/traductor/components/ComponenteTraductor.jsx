import React, { useEffect } from 'react';
import { useHandTracking } from '../../../../hooks/useHandTracking'; 
import { useSignPrediction } from '../../../../hooks/useSignPrediction';
import { loadModel } from '../../../../services/modelInferenceService';

export default function ComponenteTraductor() {
  const { videoRef, canvasRef, status, handData, startTracking, stopTracking } = useHandTracking();
  const { sentence, activePrediction } = useSignPrediction(handData);

  useEffect(() => {
    startTracking();
    return () => stopTracking();
  }, [startTracking, stopTracking]);

  useEffect(() => {
    if (typeof window.onSignLanguageInterpreted === 'function') {
      window.onSignLanguageInterpreted(sentence);
    }
  }, [sentence]);

  return (
    <div className="camara-simulada" style={{ position: 'relative', overflow: 'hidden', backgroundColor: '#090d16' }}>
      <video
        ref={videoRef}
        style={{ position: 'absolute', top: 0, left: 0, width: '100%', height: '100%', objectFit: 'cover', transform: 'scaleX(-1)' }}
        playsInline
        muted
      />
      
      <canvas
        ref={canvasRef}
        style={{ position: 'absolute', top: 0, left: 0, width: '100%', height: '100%', objectFit: 'cover', transform: 'scaleX(-1)', pointerEvents: 'none' }}
      />

      <div style={{
        position: 'absolute', top: '12px', left: '12px', 
        backgroundColor: 'rgba(15, 23, 42, 0.85)', color: '#fff', 
        padding: '6px 14px', borderRadius: '6px', fontSize: '11px', fontFamily: 'sans-serif',
        border: '1px solid rgba(255, 255, 255, 0.15)', display: 'flex', alignItems: 'center', gap: '8px',
        zIndex: 10
      }}>
        <span style={{
          width: '8px', height: '8px', borderRadius: '50%',
          backgroundColor: status === 'active' ? '#10b981' : '#f59e0b',
          display: 'inline-block'
        }} />
        {status === 'active' 
          ? `Lectura: ${activePrediction || 'Esperando mano...'}` 
          : 'Iniciando MediaPipe...'}
      </div>
    </div>
  );
}