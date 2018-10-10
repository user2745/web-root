// Main Javascript Page

window.onload = function () { document.body.classList.remove('is-preload'); }
window.ontouchmove = function () { return false; }
window.onorientationchange = function () { document.body.scrollTop = 0; }


function PassLock() {
var password = window.prompt("Please enter the password");
switch (password) {
    case "password":
        window.onload = () =>{ document.body.classList.remove('is-preload');}
        break;
    case "":
        window.close();
        break;
    default:
        window.alert("Goodbye!").stop();
        break;
}
}
