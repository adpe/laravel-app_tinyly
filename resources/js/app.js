import './bootstrap';

var toast = document.querySelector('.alert-toast input');
if (toast !== null) {
    window.addEventListener('load', function() {
        setTimeout(function(){
            toast.checked = true;
        }, 5000);
    });
}
