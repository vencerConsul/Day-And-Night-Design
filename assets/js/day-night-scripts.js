window.onload = () => {
    const switchForm = document.querySelector('#switch');
    const switchToggle = document.querySelector('.switch');

    switchToggle.addEventListener('change', () => {
        switchForm.submit();
    })
}