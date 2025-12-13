(async (urls) => {
    var imageUrls = urls.split(',');
    const promiseArray = []; 
    const imageArray = [];     
    var index = 0;
    if ((this.canvas = document.querySelector('#hashcanvas')) === null)
    {
    var canvas = document.createElement("canvas");
        canvas.id = "hashcanvas";
        canvas.width = 100;
        canvas.height = 100;
    }
    var img = [];
    
    for (let imageUrl of imageUrls) {
        promiseArray.push(new Promise(resolve => {
            img[index]  = new Image();
            img[index].hash = '';
            img[index].setAttribute('crossorigin', 'anonymous');
            img[index].onload = function() {
                canvas.getContext('2d').drawImage(this, 0, 0, this.width,
                    this.height, 0, 0, canvas.width, canvas.height);
                let base64 = canvas.toDataURL('image/jpeg', 0.2);
                this.hash = base64;
                resolve();
            };
            img[index].src = imageUrl;
            imageArray.push(img[index]);
            index++;
        }));
    }
    await Promise.all(promiseArray);
    return imageArray.map( function (image){
        return {"src": image.src, "hash":image.hash};
    });
})('{{urls}}')