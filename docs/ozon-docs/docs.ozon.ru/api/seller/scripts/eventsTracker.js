export const registrationEvents = () => {
    document.addEventListener('click', (event) => {
        const target = event.target
        const delay = setTimeout(() => {
            if (target.closest('[data-selector="tab-news"]')) {
                 window.$tracker.sendEvent({
                    object: {
                        type: 'updates_seller_docs_clicked',
                    },
                    properties: {
                        title: 'Нажатие на пункт меню "Обновления" вверху страницы.',
                        status: location.hash,
                    },
                    widget: {
                        name: 'button.TopNav',
                    },
                    actionType: 'click',
                })
            }
            if (target.closest('[data-nav]')) {
                window.$tracker.sendEvent({
                    object: {
                        type: 'nav_item',
                        id: 'data-nav',
                    },
                    properties: {
                        title: 'Нажатие на пункт меню',
                        status: location.hash,
                    },
                    widget: {
                        name: 'button.Nav',
                    },
                    actionType: 'click',
                })
            }
            if (target.closest('[data-console]')) {
                window.$tracker.sendEvent({
                    object: {
                        type: 'button',
                        id: 'data-console',
                    },
                    properties: {
                        title: 'Нажатие на кнопку консоль',
                        status: location.hash,
                    },
                    widget: {
                        name: 'button.Console',
                    },
                    actionType: 'click',
                })
            }
            if (target.closest('.CopyButton')) {
                window.$tracker.sendEvent({
                    object: {
                        type: 'button',
                        id: 'data-copy',
                    },
                    properties: {
                        title: 'Нажатие на кнопку копировать',
                        status: location.hash,
                    },
                    widget: {
                        name: 'button.Copy',
                    },
                    actionType: 'click',
                })
            }
        }, 100)
    })

    const ping = setInterval(() => {
        window.$tracker.sendEvent({
            object: {
                type: 'ping',
                id: 'ping',
            },
            properties: {
                title: 'Статус активности',
                status: location.hash,
            },
            widget: {
                name: 'ping.Hash',
            },
            actionType: 'ping',
        }, { force: true })
    }, 10000)

    const searchTracker = (value) => {
        window.$tracker.sendEvent({
            object: {
                type: 'search',
                id: 'search',
            },
            properties: {
                title: 'Поиск в документации',
                status: value,
            },
            widget: {
                name: 'search',
            },
            actionType: 'search',
        })
    }

    window.searchTracker = searchTracker
}
