export const initTracker = async () => {
    return new Promise((resolve, reject) => {
        const trackerElement = document.createElement('script')
        const element = document.getElementsByTagName('script')[0]
        const path = 'https://cdn2.ozone.ru/s3/ozts/lts/sdk.js'

        trackerElement.async = true
        trackerElement.src = path
        element.parentNode.insertBefore(trackerElement, element)

        trackerElement.onload = async () => {
            await window.$tracker.init({ namespace: 'docs' })
            resolve(window.$tracker.trackerSessionId)
        }

        trackerElement.onerror = (evt) => {
            reject(`Failed to load resource: ${evt.target.src}`)
        }
    })
}