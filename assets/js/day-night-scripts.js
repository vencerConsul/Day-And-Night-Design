window.onload = () => {
    const switchForm = document.querySelector('#switch');
    const switchToggle = document.querySelector('.switch');

    switchToggle.addEventListener('change', () => {
        switchForm.submit();
    })
    

    // 
    const selectPageNight = document.querySelectorAll('.selectPageNight');

    selectPageNight.forEach(item => {
        const selectedPageNightValue = item.options[item.selectedIndex].getAttribute('value');
        const selectedPageNightDataDay = item.options[item.selectedIndex].getAttribute('data-day');
        if(document.querySelector(`#hiddenPageDay${selectedPageNightDataDay}`).value == ''){
            document.querySelector(`#hiddenPageDay${selectedPageNightDataDay}`).setAttribute('name', `pageDay[${selectedPageNightValue}]`);
            document.querySelector(`#hiddenPageDay${selectedPageNightDataDay}`).value = selectedPageNightDataDay;
        }
        
        item.addEventListener('change', (e) => {
            const dataDay = e.target.options[e.target.selectedIndex].getAttribute('data-day');
            document.querySelector(`#hiddenPageDay${dataDay}`).setAttribute('name', `pageDay[${e.target.value}]`);
            document.querySelector(`#hiddenPageDay${dataDay}`).value = dataDay;
        })
    });
}