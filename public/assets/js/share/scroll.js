window.onload = function(){
    window.onscroll = function () {
        let t = document.documentElement.scrollTop || document.body.scrollTop;
        let header = document.querySelector('.header');
        let shopInfo = document.querySelector('.shop-info');
        if (t > 0) {
            header.style.position = 'fixed';
            shopInfo.style.marginTop = '1rem'
        } else {
            header.style.position = 'relative'
            shopInfo.style.marginTop = '0rem'
        }
    }
}