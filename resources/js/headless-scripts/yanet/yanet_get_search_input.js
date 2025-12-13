(() => 
{  
  var queryInput = document.querySelector('.input__control');
  if (!!queryInput)
  {
    return [ 
        queryInput.getBoundingClientRect().x, 
        queryInput.getBoundingClientRect().y
    ];
  }
  else {
      return 0;
  }
})();
