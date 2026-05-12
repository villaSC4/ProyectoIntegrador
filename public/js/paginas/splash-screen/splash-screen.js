document.addEventListener("DOMContentLoaded", () => {
  const pantalla = document.querySelector(".waiting-container");

  pantalla.addEventListener("click", () => {
    pantalla.classList.add("cerrar");

    setTimeout(() => {
      window.location.href = "/paginas/Login/login.html";
    }, 2800);
  });
});