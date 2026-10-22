import os
import wave
import struct
import math
import random

SAMPLE_RATE = 44100

def ensure_dir(path):
    if not os.path.exists(path):
        os.makedirs(path)

def clamp(val, min_val=-1.0, max_val=1.0):
    return max(min_val, min(max_val, val))

def save_wav(filename, samples_left, samples_right=None, sample_rate=SAMPLE_RATE):
    num_samples = len(samples_left)
    is_stereo = samples_right is not None
    nchannels = 2 if is_stereo else 1

    with wave.open(filename, 'w') as wav:
        wav.setnchannels(nchannels)
        wav.setsampwidth(2)
        wav.setframerate(sample_rate)
        
        frames = bytearray()
        for i in range(num_samples):
            # Left channel
            val_l = clamp(samples_left[i])
            int_l = int(val_l * 32767.0)
            
            if is_stereo:
                val_r = clamp(samples_right[i])
                int_r = int(val_r * 32767.0)
                frames.extend(struct.pack('<hh', int_l, int_r))
            else:
                frames.extend(struct.pack('<h', int_l))
        
        wav.writeframes(frames)
    print(f"Saved {filename} ({num_samples / sample_rate:.2f}s, {'stereo' if is_stereo else 'mono'})")

def apply_fade(samples, fade_in_sec, fade_out_sec, sample_rate=SAMPLE_RATE):
    n = len(samples)
    in_samples = int(fade_in_sec * sample_rate)
    out_samples = int(fade_out_sec * sample_rate)
    
    res = list(samples)
    for i in range(min(in_samples, n)):
        res[i] *= (i / in_samples)
    for i in range(min(out_samples, n)):
        res[n - 1 - i] *= (i / out_samples)
    return res

# --- SÍNTESIS DE SONIDOS ---

def generate_ambient_space(duration_sec=10.02):
    """Música/Ambiente espacial cinematográfico suave y majestuoso con texturas cósmicas."""
    num_samples = int(duration_sec * SAMPLE_RATE)
    left = [0.0] * num_samples
    right = [0.0] * num_samples
    
    # Acordes espaciales (D minor / F maj / A / C):
    # Frecuencias base: D2 (73.4Hz), A2 (110Hz), F3 (174.6Hz), C4 (261.6Hz), E4 (329.6Hz)
    pads = [
        (73.42, 0.22, 0.05),   # D2 Sub drone
        (110.00, 0.18, 0.08),  # A2 Warm fifth
        (174.61, 0.14, 0.12),  # F3 Minor third
        (220.00, 0.12, 0.15),  # A3
        (261.63, 0.10, 0.20),  # C4
        (329.63, 0.08, 0.25),  # E4 Lush 9th
        (440.00, 0.05, 0.30),  # A4 Shimmer
        (523.25, 0.04, 0.35)   # C5 Sparkle
    ]
    
    for i in range(num_samples):
        t = i / SAMPLE_RATE
        # LFOs lentos para movimiento cósmico continuo
        lfo_slow = math.sin(2 * math.pi * 0.1 * t)
        lfo_filter = 0.5 + 0.5 * math.sin(2 * math.pi * 0.25 * t)
        
        sample_l = 0.0
        sample_r = 0.0
        
        for freq, amp, pan in pads:
            # Vibrato/Detune sutil para textura analógica cinematográfica
            detune_l = freq * (1.0 + 0.003 * math.sin(2 * math.pi * 0.15 * t + pan * 5))
            detune_r = freq * (1.0 + 0.003 * math.cos(2 * math.pi * 0.18 * t + pan * 7))
            
            sig_l = math.sin(2 * math.pi * detune_l * t) + 0.25 * math.sin(4 * math.pi * detune_l * t)
            sig_r = math.sin(2 * math.pi * detune_r * t) + 0.25 * math.sin(4 * math.pi * detune_r * t)
            
            # Pan estéreo
            pan_l = 0.5 - 0.4 * math.sin(2 * math.pi * 0.12 * t + pan)
            pan_r = 1.0 - pan_l
            
            sample_l += sig_l * amp * pan_l
            sample_r += sig_r * amp * pan_r
            
        # Shimmer cósmico muy suave (ruido filtrado suave)
        noise = (random.random() * 2.0 - 1.0) * 0.015 * lfo_filter
        
        left[i] = sample_l * 0.75 + noise
        right[i] = sample_r * 0.75 + noise

    # Fade in y out cruzado para loop perfecto sin chasquidos
    left = apply_fade(left, 0.4, 0.4)
    right = apply_fade(right, 0.4, 0.4)
    return left, right

def generate_rabbit_jump():
    """Sonido cómico y divertido de caricatura: salto de conejo estilo 'boing'."""
    duration = 0.65
    num_samples = int(duration * SAMPLE_RATE)
    samples = [0.0] * num_samples
    
    for i in range(num_samples):
        t = i / SAMPLE_RATE
        progress = t / duration
        
        # Frecuencia asciende rápido (booo-ing!) de 180Hz a 620Hz
        base_freq = 180.0 + 440.0 * (progress ** 0.6)
        # Vibrato elástico estilo resorte de dibujos animados
        vibrato_rate = 22.0 * (1.0 - progress * 0.5)
        vibrato_amp = 30.0 * math.exp(-3.0 * progress)
        freq = base_freq + vibrato_amp * math.sin(2 * math.pi * vibrato_rate * t)
        
        # Envolvente percusiva con rebote
        envelope = math.exp(-3.8 * progress) * (1.0 + 0.3 * math.sin(2 * math.pi * 40 * t))
        
        # Forma de onda con armónico brillante para caricatura
        wave_val = (math.sin(2 * math.pi * freq * t) + 
                    0.4 * math.sin(4 * math.pi * freq * t) +
                    0.15 * math.sin(6 * math.pi * freq * t))
        
        samples[i] = wave_val * envelope * 0.65
        
    return apply_fade(samples, 0.005, 0.05)

def generate_galaxy_explosion():
    """Explosión cinematográfica estelar suave con estela cósmica y sub-graves."""
    duration = 2.4
    num_samples = int(duration * SAMPLE_RATE)
    samples_l = [0.0] * num_samples
    samples_r = [0.0] * num_samples
    
    for i in range(num_samples):
        t = i / SAMPLE_RATE
        progress = t / duration
        
        # Impacto sub-grave descendente (140Hz -> 38Hz)
        sub_freq = 140.0 * math.exp(-4.5 * progress) + 38.0
        sub_env = math.exp(-2.8 * progress)
        sub_wave = math.sin(2 * math.pi * sub_freq * t) * sub_env * 0.7
        
        # Estela de polvo cósmico (ruido filtrado)
        noise_env = math.exp(-2.2 * progress) * (0.8 + 0.2 * math.sin(2 * math.pi * 8 * t))
        noise_l = (random.random() * 2.0 - 1.0) * noise_env * 0.35
        noise_r = (random.random() * 2.0 - 1.0) * noise_env * 0.35
        
        # Resplandor brillante de estrellas
        shimmer_freq = 1200.0 * (1.0 - progress * 0.4)
        shimmer = math.sin(2 * math.pi * shimmer_freq * t) * math.exp(-4.0 * progress) * 0.15
        
        samples_l[i] = (sub_wave + noise_l + shimmer) * 0.65
        samples_r[i] = (sub_wave + noise_r + shimmer) * 0.65
        
    return apply_fade(samples_l, 0.005, 0.2), apply_fade(samples_r, 0.005, 0.2)

def generate_planet_magic():
    """Efecto mágico celestial de campanas y estrellas al aparecer planetas."""
    duration = 1.8
    num_samples = int(duration * SAMPLE_RATE)
    samples_l = [0.0] * num_samples
    samples_r = [0.0] * num_samples
    
    # Arpegio estelar pentatónico: C6, E6, G6, B6, C7, E7
    notes = [1046.50, 1318.51, 1567.98, 1975.53, 2093.00, 2637.02]
    note_interval = 0.09
    
    for idx, freq in enumerate(notes):
        start_time = idx * note_interval
        start_idx = int(start_time * SAMPLE_RATE)
        pan = (idx / (len(notes) - 1)) * 0.8 + 0.1  # 0.1 a 0.9 stereo
        
        for i in range(start_idx, num_samples):
            t_note = (i - start_idx) / SAMPLE_RATE
            note_env = math.exp(-3.5 * t_note)
            
            # Campana pura con armónico campana
            chime = (math.sin(2 * math.pi * freq * t_note) +
                     0.3 * math.sin(2 * math.pi * freq * 2.0 * t_note) +
                     0.1 * math.sin(2 * math.pi * freq * 3.0 * t_note)) * note_env * 0.22
            
            samples_l[i] += chime * (1.0 - pan)
            samples_r[i] += chime * pan

    return apply_fade(samples_l, 0.01, 0.15), apply_fade(samples_r, 0.01, 0.15)

def generate_warp_speed():
    """Efecto futurista de desplazamiento y aceleración hiperespacial."""
    duration = 1.4
    num_samples = int(duration * SAMPLE_RATE)
    samples_l = [0.0] * num_samples
    samples_r = [0.0] * num_samples
    
    for i in range(num_samples):
        t = i / SAMPLE_RATE
        progress = t / duration
        
        # Efecto Doppler y barrido de frecuencias
        freq_sweep = 120.0 + 1600.0 * (progress ** 2.2)
        whoosh_env = math.sin(math.pi * (progress ** 0.8))
        
        # Tono láser resonante
        laser = math.sin(2 * math.pi * freq_sweep * t) * 0.35
        # Ruido de turbina de nave
        noise = (random.random() * 2.0 - 1.0) * 0.4
        
        # Panning de izquierda a derecha simulando paso a ultra velocidad
        pan_l = math.cos(math.pi * 0.5 * progress)
        pan_r = math.sin(math.pi * 0.5 * progress)
        
        samples_l[i] = (laser + noise) * whoosh_env * pan_l * 0.6
        samples_r[i] = (laser + noise) * whoosh_env * pan_r * 0.6
        
    return apply_fade(samples_l, 0.05, 0.1), apply_fade(samples_r, 0.05, 0.1)

def generate_welcome_sound():
    """Sonido de bienvenida al entrar al sistema / iniciar misión (fanfarria alegre)."""
    duration = 1.3
    num_samples = int(duration * SAMPLE_RATE)
    samples_l = [0.0] * num_samples
    samples_r = [0.0] * num_samples
    
    # Secuencia C Major: C5 (523Hz), E5 (659Hz), G5 (784Hz), C6 (1046Hz)
    chords = [
        (0.00, 523.25, 0.35),
        (0.12, 659.25, 0.45),
        (0.24, 783.99, 0.55),
        (0.38, 1046.50, 0.70)
    ]
    
    for start_t, freq, amp in chords:
        start_idx = int(start_t * SAMPLE_RATE)
        for i in range(start_idx, num_samples):
            t_rel = (i - start_idx) / SAMPLE_RATE
            env = math.exp(-3.2 * t_rel)
            
            # Sonido estilo sintetizador brillante de consola
            synth = (math.sin(2 * math.pi * freq * t_rel) +
                     0.35 * math.sin(4 * math.pi * freq * t_rel) +
                     0.15 * math.sin(6 * math.pi * freq * t_rel)) * env * amp * 0.3
            
            samples_l[i] += synth
            samples_r[i] += synth
            
    return apply_fade(samples_l, 0.01, 0.1), apply_fade(samples_r, 0.01, 0.1)

def generate_victory_level():
    """Fanfarria triunfal de videojuego infantil cuando se completa un nivel con éxito."""
    duration = 2.4
    num_samples = int(duration * SAMPLE_RATE)
    samples_l = [0.0] * num_samples
    samples_r = [0.0] * num_samples
    
    # Melodía de victoria: G4, G4, G4, C5 (largo), E5, G5
    melody = [
        (0.00, 0.14, 392.00, 0.4), # G4
        (0.15, 0.14, 392.00, 0.4), # G4
        (0.30, 0.14, 392.00, 0.4), # G4
        (0.48, 0.45, 523.25, 0.6), # C5
        (0.95, 0.25, 659.25, 0.6), # E5
        (1.22, 0.95, 783.99, 0.8), # G5 Triunfal con brillo
    ]
    
    for start_t, dur, freq, amp in melody:
        start_idx = int(start_t * SAMPLE_RATE)
        end_idx = min(num_samples, start_idx + int(dur * 1.5 * SAMPLE_RATE))
        for i in range(start_idx, end_idx):
            t_rel = (i - start_idx) / SAMPLE_RATE
            env = math.exp(-2.2 * t_rel)
            
            # Onda brillante rica
            tone = (math.sin(2 * math.pi * freq * t_rel) +
                    0.4 * math.sin(4 * math.pi * freq * t_rel) +
                    0.2 * math.sin(6 * math.pi * freq * t_rel) +
                    0.1 * math.sin(8 * math.pi * freq * t_rel)) * env * amp * 0.35
            
            # Pequeño brillo estéreo
            samples_l[i] += tone * (1.0 + 0.1 * math.sin(2 * math.pi * 5 * t_rel))
            samples_r[i] += tone * (1.0 - 0.1 * math.sin(2 * math.pi * 5 * t_rel))
            
    # Añadir cascada de campanillas mágicas al final
    sparkle_start = int(1.3 * SAMPLE_RATE)
    for i in range(sparkle_start, num_samples):
        t_sp = (i - sparkle_start) / SAMPLE_RATE
        sparkle = 0.06 * math.sin(2 * math.pi * (1800 + 400 * math.sin(30 * t_sp)) * t_sp) * math.exp(-1.8 * t_sp)
        samples_l[i] += sparkle
        samples_r[i] += sparkle
        
    return apply_fade(samples_l, 0.01, 0.15), apply_fade(samples_r, 0.01, 0.15)

def mix_into(target, source, start_time, volume=1.0, sample_rate=SAMPLE_RATE):
    """Mezcla una pista de audio sobre otra en un timestamp específico."""
    start_idx = int(start_time * sample_rate)
    for i in range(len(source)):
        tgt_idx = start_idx + i
        if tgt_idx < len(target):
            target[tgt_idx] += source[i] * volume

def generate_synchronized_conejo_soundtrack(duration_sec=10.02):
    """
    Banda sonora cinematográfica de 10.02s EXACTAMENTE sincronizada con el video:
    - Base: Pad cósmico ambiental continuo.
    - 0.8s: Desplazamiento estelar de la nave / conejo.
    - 2.8s: Destello mágico de planetas.
    - 4.8s: Salto caricaturesco y divertido del conejo.
    - 6.6s: Explosión estelar cósmica suave.
    - 8.5s: Segundo salto y remolino espacial para retorno continuo.
    """
    print("Generating full synchronized soundtrack for conejo_espacial.mp4 (10.02s)...")
    bg_l, bg_r = generate_ambient_space(duration_sec)
    
    # Efectos sincronizados
    warp_l, warp_r = generate_warp_speed()
    magic_l, magic_r = generate_planet_magic()
    jump = generate_rabbit_jump()
    boom_l, boom_r = generate_galaxy_explosion()
    
    # Mezcla en los tiempos exactos de la escena
    mix_into(bg_l, warp_l, 0.8, volume=0.55)
    mix_into(bg_r, warp_r, 0.8, volume=0.55)
    
    mix_into(bg_l, magic_l, 2.8, volume=0.5)
    mix_into(bg_r, magic_r, 2.8, volume=0.5)
    
    mix_into(bg_l, jump, 4.8, volume=0.6)
    mix_into(bg_r, jump, 4.8, volume=0.6)
    
    mix_into(bg_l, boom_l, 6.6, volume=0.45)
    mix_into(bg_r, boom_r, 6.6, volume=0.45)
    
    mix_into(bg_l, jump, 8.5, volume=0.5)
    mix_into(bg_r, jump, 8.5, volume=0.5)
    
    # Normalización para evitar clipping
    max_val = max(max(abs(x) for x in bg_l), max(abs(x) for x in bg_r))
    if max_val > 0.95:
        scale = 0.92 / max_val
        bg_l = [x * scale for x in bg_l]
        bg_r = [x * scale for x in bg_r]
        
    return bg_l, bg_r

def main():
    audio_dir = r"c:\xampp\htdocs\LSB\assets\audio"
    ensure_dir(audio_dir)
    
    print("Creating audio directory at:", audio_dir)
    
    # 1. Pista completa sincronizada con el video del conejo (10.02s)
    conejo_l, conejo_r = generate_synchronized_conejo_soundtrack(10.02)
    save_wav(os.path.join(audio_dir, "conejo_soundtrack.wav"), conejo_l, conejo_r)
    
    # 2. Ambiente espacial suave continuo
    amb_l, amb_r = generate_ambient_space(10.02)
    save_wav(os.path.join(audio_dir, "ambiente_espacial.wav"), amb_l, amb_r)
    
    # 3. Salto de caricatura del conejo
    jump = generate_rabbit_jump()
    save_wav(os.path.join(audio_dir, "conejo_salto.wav"), jump)
    
    # 4. Explosión cósmica
    boom_l, boom_r = generate_galaxy_explosion()
    save_wav(os.path.join(audio_dir, "explosion_galaxia.wav"), boom_l, boom_r)
    
    # 5. Magia de planetas
    mag_l, mag_r = generate_planet_magic()
    save_wav(os.path.join(audio_dir, "magia_planetas.wav"), mag_l, mag_r)
    
    # 6. Desplazamiento y velocidad cósmica
    warp_l, warp_r = generate_warp_speed()
    save_wav(os.path.join(audio_dir, "velocidad_espacio.wav"), warp_l, warp_r)
    
    # 7. Sonido de bienvenida
    wel_l, wel_r = generate_welcome_sound()
    save_wav(os.path.join(audio_dir, "bienvenida_mision.wav"), wel_l, wel_r)
    
    # 8. Fanfarria de victoria de nivel
    vic_l, vic_r = generate_victory_level()
    save_wav(os.path.join(audio_dir, "victoria_nivel.wav"), vic_l, vic_r)
    
    print("\nALL 8 AUDIO ASSETS SUCCESSFULLY GENERATED IN assets/audio/!")

if __name__ == "__main__":
    main()
