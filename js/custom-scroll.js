document.addEventListener('click', function (event) {
    var link = event.target.closest('a[href^="#"]');
    if (!link) {
        return;
    }
    var id = link.getAttribute('href').slice(1);
    if (!id) {
        return;
    }
    var target = document.getElementById(id);
    if (!target) {
        return;
    }
    event.preventDefault();
    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
});
