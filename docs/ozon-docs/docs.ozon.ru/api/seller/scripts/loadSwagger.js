export const loadSwagger = async (resolve) => {
    const baseFields = {
        "consumes": [
            "application/json"
        ],
        "produces": [
            "application/json"
        ],
        "definitions": {},
        "parameters": {},
        "paths": {},
        "info": {
            "title": "seller-api-doc",
            "version": "release-xxxxxx",
            "x-logo": {
                "url": "./ozon-seller-logo.svg",
                "backgroundColor": null,
                "altText": "Seller API Doc"
            }
        },
        "swagger": "2.0",
        "host": "api-seller.ozon.ru"
    }

    async function loadJson(url) {
        const resp = await fetch(url);
        return await resp.json();
    }

    const sellerApiSwaggerJsonLink = "./swagger.json?" + Date.now();

    const initialSpec = await loadJson(sellerApiSwaggerJsonLink);
    return await _.merge(baseFields, initialSpec)
}