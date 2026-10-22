const API = "http://localhost/api/users"

//Listado de los usuarios
async function loadUsers() {
    const res = await fetch(API);
    const users = await res.json();
    const table = document.getElementById("usersTable");
    table.innerHTML = "";

    users.forEach(u => {
        table.innerHTML += `
            <tr>
        <td>${u.usuario || u.nombre || (u.nombres + " " + u.apellidos)}</td>
        <td>${u.email}</td>
        <td>${u.telefono || u.phone || ""}</td>

                <td>
                    <button class="btn btn-sm btn-warning" onclick="editUser('${u.uid}')">Editar</button>
                    <button class="btn btn-sm btn-danger" onclick="deleteUser('${u.uid}')">Eliminar</button>
                </td>
            </tr>
        `;
    });
}

async function deleteUser(uid) {
    const result = await Swal.fire({
        title: "¿Eliminar usuario?",
        text: "Esta acción se puede deshacer",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Si, eliminar",
        cancelButtonText: "Cancelar"
    });

    if (!result.isConfirmed) return;

    await fetch(API + "/" + uid, { method: "DELETE" });
    await Swal.fire({
    icon: "success",
    title: "Eliminado",
    text: "El usuario fue eliminado correctamente",
    timer: 2000,
    showConfirmButton: false
});

loadUsers();
}


/** FORMULARIO **/
async function openModal() {
    const res = await fetch("form.html");
    const html = await res.text();
    document.getElementById("modalBody").innerHTML = html;

    return new Promise((resolve) => {
        $('#userModal').one('shown.bs.modal', function () {
            resolve();
        });
        $('#userModal').modal('show');
    });
}


async function newUser() {
    document.getElementById("modalTitle").innerText = "Nuevo Usuario";
    await openModal();
}


async function editUser(uid) {
    document.getElementById("modalTitle").innerText = "Editar Usuario";
    await openModal();

      const res = await fetch(API + "/" + uid);
        const u = await res.json();

            document.getElementById("uid").value = u.uid;
    
                 if (u.usuario) {
                    const partes = u.usuario.trim().split(" ");

                    if (partes.length > 1) {
                        document.getElementById("apellidos").value = partes.pop();
                        document.getElementById("nombres").value = partes.join(" "); 

                     } else {
          
                        document.getElementById("nombres").value = u.usuario;
                        document.getElementById("apellidos").value = "";
                    }
                } else {
        
                    document.getElementById("nombres").value = u.nombres || "";
                    document.getElementById("apellidos").value = u.apellidos || "";
                }
    
    document.getElementById("email").value = u.email;
    document.getElementById("phone").value = u.telefono || "";

        email.value = u.email;
        phone.value = u.telefono || "";


}

async function saveUser() {
    const uid = document.getElementById("uid").value;

    const data = {
    usuario: apellidos.value ? `${nombres.value} ${apellidos.value}` : nombres.value,
    email: email.value,
    password_hash: phone.value
}


    if (uid) {
        await fetch(API + "/" + uid, {
            method: "PUT",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        });

        await Swal.fire({
            icon: "success",
            title: "Actualizado",
            text: "El usuario fue actualizado correctamente",
            timer: 2000,
            showConfirmButton: false
        });
    } else {
        await fetch(API, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        });

        await Swal.fire({
            icon: "success",
            title: "Creado",
            text: "El usuario fue adicionado correctamente",
            timer: 3000,
            showConfirmButton: false
        });
    }
    $('#userModal').modal('hide');
    loadUsers();
}

