(()=>{
    let reviews = [];
    let elements = document.querySelectorAll('p[itemprop="itemReviewed"]');
    elements.forEach((e) => {
        if (e.previousElementSibling) {
            rating = e.previousElementSibling.querySelector('meta[itemprop="ratingValue"]').getAttribute('content');
            description = e.parentNode.parentNode.parentNode.querySelector('p[data-marker*="text-section/text"]').textContent;
            console.log(description);
            title =  e.querySelector('span[itemprop="name"]').textContent;
            reviews.push({
                'title' : title,
                'score' : rating,
                'description' : description
            });
        }
    });
    
    return reviews;
})();