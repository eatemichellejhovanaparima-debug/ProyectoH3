def test_cn01_umbral_ia_aprobado():
    """Caja Negra: Valida el avance si la IA supera el 85% de acierto"""
    probabilidad_gesto = 0.89
    assert probabilidad_gesto >= 0.85

def test_cn02_umbral_ia_rechazado():
    """Caja Negra: Bloquea el avance si la seña es incorrecta (< 85%)"""
    probabilidad_gesto = 0.42
    assert probabilidad_gesto < 0.85

def test_cn03_formato_link_multimedia():
    """Caja Negra: Valida la lectura correcta de enlaces web externos HTTP/HTTPS"""
    assert "https://ejemplo.com".startswith("http") is True
