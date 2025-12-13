(() => 
{  
  //Проверка на бан прокси
  let headerElement = document.querySelector('.header__code');
  return !!headerElement && headerElement.innerText.trim() === '403';
})()
