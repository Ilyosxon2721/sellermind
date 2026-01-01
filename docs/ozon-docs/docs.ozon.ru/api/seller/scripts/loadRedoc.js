export const loadRedoc = (session) => {
    return new Promise(async resolve => {
        localStorage.setItem('isShowAppstoreBanner', 'show');

        const version = localStorage.getItem('version')
        let redocVersionBundle = 'v7.30'

        if (version) {
            redocVersionBundle = version
        }

        const scriptsRedoc = document.createElement('script')
        scriptsRedoc.setAttribute('src', `../../../cdn2.ozone.ru/s3/redoc/scripts/${redocVersionBundle}/redoc.standalone.js`)

        document.body.insertAdjacentElement('beforeend', scriptsRedoc)

        scriptsRedoc.onload = function () {
            resolve(true)
        }
    })
}
