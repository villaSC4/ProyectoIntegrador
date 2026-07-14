# Motor Python de señas de MediSign

Este motor no reemplaza Laravel ni MediaPipe.

- MediaPipe en el navegador sigue detectando manos, rostro y torso.
- Laravel sigue manejando login, paneles, MySQL y pantallas.
- Python solo compara las coordenadas aprendidas y responde qué seña parece.

## Cómo iniciar

Desde la raíz del proyecto:

```bat
iniciar-python-senas.bat
```

O manualmente:

```bat
python python-senas\server.py
```

Debe mostrar:

```text
MediSign senas Python escuchando en http://127.0.0.1:5055
```

## Cómo probar si está vivo

Abre en el navegador:

```text
http://127.0.0.1:5055/health
```

Debe responder algo parecido a:

```json
{"ok": true, "motor": "python", "version_modelo": "v4.0"}
```

## Qué pasa si Python está apagado

Laravel usa el comparador PHP anterior como respaldo, así el traductor no se cae.

## Configuración opcional

Laravel busca Python en:

```text
http://127.0.0.1:5055
```

Si algún día cambia el puerto, agrega o modifica esta línea en `.env`:

```env
SENAS_PYTHON_URL=http://127.0.0.1:5055
```
