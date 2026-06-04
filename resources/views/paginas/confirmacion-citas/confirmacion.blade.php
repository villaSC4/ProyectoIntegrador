<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cita Confirmada - MediSign</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="{{ asset('css/paginas/confirmacion-citas/confirmacion-citas.css') }}">
</head>
<body>
    <div class="main-container">
        <div class="confirmation-overlay">
            <div class="top-section">
                <div class="check-circle">✓</div>
                <h1>¡Cita Confirmada<br>Automáticamente!</h1>
                <p>Tu cita médica ha sido registrada exitosamente</p>
            </div>
            
            <div class="details-box">
                <p class="label">Especialidad</p>
                <h2 class="specialty-name">{{ $especialidad->nombre ?? 'Especialidad Médica' }}</h2>
                
                <p class="label" style="margin-top: 15px; color: #4b5563;">Médico Especialista</p>
                <h3 style="color: #1f2937; font-size: 19px; font-weight: 700; margin-bottom: 20px; letter-spacing: -0.02em;">
                    {{ $doctor->nombre }}
                </h3>
                
                <div class="info-grid">
                    <div class="info-item">
                        <span class="icon">📅</span>
                        <div class="text-info">
                            <p class="bold">{{ $horario->dia_semana }}</p>
                            <p class="light">Turno {{ ucfirst($horario->turno) }}</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <span class="icon">🕒</span>
                        <div class="text-info">
                            <p class="bold">
                                {{ \Carbon\Carbon::parse($horario->hora_inicio)->format('g:i A') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="footer-card">
                <div class="status">✔️ Estado: Confirmado</div>
                <p class="notice">Recibirás un recordatorio antes de tu cita</p>
                <p class="redirect">Redirigiendo al inicio...</p>
            </div>
        </div>

        <div class="bottom-nav">
            <a href="{{ route('home') }}" class="nav-btn btn-left">
                <span class="btn-icon">🏠</span> Volver a Inicio
            </a>
            
            <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" class="nav-btn btn-right" style="border: none; background: none; cursor: pointer; font-family: inherit;">
                    Salir <span class="btn-icon">⎋</span>
                </button>
            </form>
        </div>
    </div>

    <script>
        setTimeout(() => {
            window.location.href = "{{ route('home') }}";
        }, 15000);
    </script>
</body>
</html>