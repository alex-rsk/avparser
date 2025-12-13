(() =>
{
    let loader = document.querySelector('span[data-serp-spinner]');
    return loader.offsetWidth>0 && loader.offsetHeight>0;
})();