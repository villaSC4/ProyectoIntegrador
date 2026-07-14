import React, { useEffect } from 'react';
import { useHandTracking } from '../../../../hooks/useHandTracking'; 
import { useSignPrediction } from '../../../../hooks/useSignPrediction';

export default function ComponenteTraductor() {
  const { videoRef, canvasRef, status, handData, startTracking, stopTracking } = useHandTracking();
  const { 
    sentence, 
    activePrediction, 
    alternatives, 
    appendCharacter, 
    skipPrediction,
    deleteLastChar,
    clearSentence
  } = useSignPrediction(handData);

  useEffect(() => {
    startTracking();
    return () => stopTracking();
  }, [startTracking, stopTracking]);

  useEffect(() => {
    if (typeof window.onSignLanguageInterpreted === 'function') {
      window.onSignLanguageInterpreted(sentence);
    }
  }, [sentence]);

  useEffect(() => {
    window.onBorrarUltimaPalabra = () => {
      deleteLastChar();
    };
    window.onLimpiarTodo = () => {
      clearSentence();
    };

    return () => {
      delete window.onBorrarUltimaPalabra;
      delete window.onLimpiarTodo;
    };
  }, [deleteLastChar, clearSentence]);

  const obtenerTextoEstado = () => {
    if (status !== 'active') return 'Iniciando cámara y MediaPipe...';
    if (!activePrediction || activePrediction === 'nothing' || activePrediction === 'Ninguna') {
      return 'Coloque su mano en cámara';
    }
    if (activePrediction === 'Alineando...') {
      return 'Alineando postura...';
    }
    return `Gesto activo: ${activePrediction.toUpperCase()}`;
  };

  return (
    <div className="camara-simulada" style={{ position: 'relative', overflow: 'hidden', backgroundColor: '#090d16', borderRadius: '12px', width: '100%', height: '100%' }}>
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
        position: 'absolute', top: '16px', left: '16px', 
        backgroundColor: 'rgba(9, 13, 22, 0.85)', color: '#fff', 
        padding: '8px 16px', borderRadius: '8px', fontSize: '12px', fontFamily: 'Inter, sans-serif',
        border: '1px solid rgba(255, 255, 255, 0.1)', display: 'flex', alignItems: 'center', gap: '10px',
        backdropFilter: 'blur(8px)', zIndex: 10, boxShadow: '0 4px 12px rgba(0,0,0,0.25)'
      }}>
        <span style={{
          width: '8px', height: '8px', borderRadius: '50%',
          backgroundColor: status === 'active' ? '#10b981' : '#f59e0b',
          display: 'inline-block',
          boxShadow: status === 'active' ? '0 0 8px #10b981' : '0 0 8px #f59e0b'
        }} />
        <span style={{ fontWeight: '500', letterSpacing: '0.3px' }}>
          {obtenerTextoEstado()}
        </span>
      </div>

      {status === 'active' && alternatives.length > 0 && (
        <div style={{
          position: 'absolute', bottom: '20px', left: '50%', transform: 'translateX(-50%)',
          backgroundColor: 'rgba(9, 13, 22, 0.95)', padding: '12px 20px', borderRadius: '14px',
          border: '1px solid rgba(168, 85, 247, 0.5)',
          display: 'flex', flexDirection: 'column', alignItems: 'center', gap: '10px',
          backdropFilter: 'blur(16px)', zIndex: 11, boxShadow: '0 10px 40px rgba(0,0,0,0.6)'
        }}>
          <span style={{ fontSize: '10px', color: '#a855f7', fontWeight: 'bold', textTransform: 'uppercase', letterSpacing: '1.2px' }}>
            ¿Seña dudosa? Selecciona o descarta:
          </span>
          
          <div style={{ display: 'flex', gap: '8px', alignItems: 'center' }}>
            {alternatives.map((alt) => (
              <button
                key={alt.label}
                onClick={() => appendCharacter(alt.label)}
                style={{
                  backgroundColor: 'rgba(255, 255, 255, 0.08)',
                  border: '1px solid rgba(255, 255, 255, 0.15)',
                  color: '#fff', padding: '6px 14px', borderRadius: '8px',
                  fontSize: '11px', fontWeight: '600', cursor: 'pointer',
                  display: 'flex', gap: '6px', alignItems: 'center',
                  transition: 'all 0.2s ease'
                }}
                onMouseOver={(e) => { e.currentTarget.style.backgroundColor = 'rgba(168, 85, 247, 0.2)'; e.currentTarget.style.borderColor = '#a855f7'; }}
                onMouseOut={(e) => { e.currentTarget.style.backgroundColor = 'rgba(255, 255, 255, 0.08)'; e.currentTarget.style.borderColor = 'rgba(255, 255, 255, 0.15)'; }}
              >
                <span>{alt.label.toUpperCase()}</span>
                <span style={{ fontSize: '9px', color: '#94a3b8' }}>{alt.percent}%</span>
              </button>
            ))}

            <span style={{ width: '1px', height: '18px', backgroundColor: 'rgba(255,255,255,0.15)', margin: '0 4px' }} />

            <button
              onClick={skipPrediction}
              style={{
                backgroundColor: 'rgba(239, 68, 68, 0.15)',
                border: '1px solid rgba(239, 68, 68, 0.35)',
                color: '#f87171', padding: '6px 14px', borderRadius: '8px',
                fontSize: '11px', fontWeight: 'bold', cursor: 'pointer',
                transition: 'all 0.2s ease'
              }}
              onMouseOver={(e) => { e.currentTarget.style.backgroundColor = 'rgba(239, 68, 68, 0.3)'; e.currentTarget.style.borderColor = '#ef4444'; }}
              onMouseOut={(e) => { e.currentTarget.style.backgroundColor = 'rgba(239, 68, 68, 0.15)'; e.currentTarget.style.borderColor = 'rgba(239, 68, 68, 0.35)'; }}
            >
              NINGUNA ✕
            </button>
          </div>
          
          <span style={{ fontSize: '9px', color: '#64748b' }}>
            La lectura se reactivará automáticamente si no seleccionas nada en unos segundos.
          </span>
        </div>
      )}
    </div>
  );
}