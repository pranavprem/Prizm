function addToCart(id) {
    var tokenMeta = document.querySelector('meta[name="csrf-token"]');
    var token = tokenMeta ? tokenMeta.getAttribute('content') : '';
    var body = new URLSearchParams();
    body.set('pid', String(id));
    body.set('csrf', token || '');

    fetch('addtocart.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': token || ''
        },
        body: body.toString()
    }).then(function (response) {
        return response.text().then(function (text) {
            return {
                text: text.trim(),
                count: response.headers.get('X-Cart-Count')
            };
        });
    }).then(function (result) {
        var status = document.getElementById('cart-status');
        if (status) {
            status.textContent = result.text;
            status.classList.add('is-visible');
        }
        if (result.count !== null) {
            var badge = document.querySelector('[data-cart-count]');
            if (badge) {
                badge.textContent = result.count;
                badge.hidden = result.count === '0';
            }
        }
    }).catch(function () {
        var status = document.getElementById('cart-status');
        if (status) {
            status.textContent = 'Could not reach the cart.';
            status.classList.add('is-visible');
        }
    });
}
