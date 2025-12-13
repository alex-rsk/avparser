(() => 
{  
  let result = [];
  let nodeListContainers = document.querySelectorAll('div[id^="fbBrowseScrollingPagerContainer"]');
  let arContainers = Array.from(nodeListContainers);
  //Отфильтровать видимые контейнеры ленты (и те, которые чуть позади)
  let visibleContainers = arContainers.filter((element) => { 
      return element.getBoundingClientRect().top >= -300 }
  );
  for (let container of visibleContainers)
  {
      result.push(container.getAttribute('id'));
  }  
  return result;
})();