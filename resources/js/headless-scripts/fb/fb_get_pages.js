((containers)=> {
    let results = [];
    let selectors = containers.split(',');
    if (document.querySelector('div[data-testid="search_empty_results_card"]')!== null)
    {
        return false;
    }
    for (let selector of selectors)
    {        
        let containerElement = document.querySelector('#'+selector.trim());
        if (containerElement == null)
        {
            break;
        }
        let children = containerElement.querySelectorAll('._3u1._gli._6pe1._87m1');
        for (let child of children)
        {
            let now = new Date();
            let offsetMs = now.getTimezoneOffset() * 60 * 1000;
            let dateLocal = new Date(now.getTime() - offsetMs);
            let dateStr = dateLocal.toISOString().slice(0, 19).replace("T", " ");

            let elementData = JSON.parse(child.dataset.bt);
            let pageName = child.querySelector('._32mo').innerText.normalize('NFKD');
            results.push(
                {
                    "page_name" : pageName, 
                    "picture": child.querySelector('img').getAttribute('src'),
                    "page_id": elementData.id,
                    "language" : "rus",
                    "last_visit" : dateStr
                }
            );
        }
    }
    return results;
})('{{containers}}');