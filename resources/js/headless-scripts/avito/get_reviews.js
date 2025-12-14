(()=>{
    let reviews = [];
    let elements = document.querySelectorAll('p[itemprop="itemReviewed"]');
    elements.forEach((e) => {
        if (e.previousElementSibling) {
            rating = e.previousElementSibling.querySelector('meta[itemprop="ratingValue"]').getAttribute('content');
            title =  e.querySelector('span[itemprop="name"]').textContent;
            reviews.push({
                'title' : title,
                'score' : rating,
            });
        }
    });
    
    return reviews;
})();