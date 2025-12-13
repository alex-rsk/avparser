((query) => 
{  
  var queryInput = document.querySelector('input');
  return [ 
      queryInput.getBoundingClientRect().x, 
      queryInput.getBoundingClientRect().y
  ];
})();
