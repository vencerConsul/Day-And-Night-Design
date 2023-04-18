window.onload = () => {
    const switchForm = document.querySelector('#switch');
    const switchToggle = document.querySelector('.switch');

    switchToggle.addEventListener('change', () => {
        switchForm.submit();
    })

    const wpBody = document.querySelector('.settings_page_day-and-night-settings #wpbody-content');
    const vHead = document.querySelector('#v-head');
    wpBody.style.paddingTop = `${vHead.offsetHeight}px`;

    const selectPageNight = document.querySelectorAll('.selectPageNight');
    selectPageNight.forEach(item => {
        item.addEventListener('change', (e) => {
            const dataDay = e.target.options[e.target.selectedIndex].getAttribute('data-day');
            document.querySelector(`#hiddenPageDay${dataDay}`).setAttribute('name', `pageDay[${e.target.value}]`);
            document.querySelector(`#hiddenPageDay${dataDay}`).value = dataDay;
        });
    });

    const saveSettings = document.querySelector('.save_settings');
    saveSettings.addEventListener('click', ()=>{
        document.querySelector('#save_settings').submit();
    });
}