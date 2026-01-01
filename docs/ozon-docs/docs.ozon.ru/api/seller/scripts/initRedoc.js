export const initRedoc = (options, data) => {
    if (data) {
        Redoc.init(
            data,
            options,
            document.getElementById("redoc-container")
        )
    }
}

