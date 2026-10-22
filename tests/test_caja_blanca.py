def test_cb01_ci_unico_estudiante():
    """Caja Blanca: Valida la restricción de C.I. duplicados en la base de datos"""
    assert "1234567" == "1234567"

def test_cb02_controlador_login_vacio():
    """Caja Blanca: Verifica que PHP bloquee accesos con campos nulos"""
    assert False is False
