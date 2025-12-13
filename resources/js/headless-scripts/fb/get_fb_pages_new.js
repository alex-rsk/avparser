((chunkSize, flush)=> {
    let iChunkSize = parseInt(chunkSize);
    let collected  = [];
    if (flush == '0')
    {
        if (pages.length > iChunkSize)
        {
            collected = pages.splice(0, iChunkSize);
        }
        let result = {
            "pages" :  collected,
            "error" :  error,
            "requests" : requests
        };
        return result;
    }
    else {
        collected = pages;
        pages = [];
        let result = {
            "pages" :  collected,
            "error" :  error,
            "requests" : requests
        };
        return result;
    }
})('{{chunkSize}}','{{flush}}');