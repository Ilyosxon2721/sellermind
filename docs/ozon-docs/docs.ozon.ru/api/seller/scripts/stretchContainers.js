export const stretchContainers = () => {
  let containers;

  const redocContainer = document.getElementById('redoc-container');

  const observer = new MutationObserver(callback);

  const config = {
    attributes: true,
    childList: true,
    subtree: true
  };

  observer.observe(redocContainer, config);

  function callback (mutationsList, observer) {
    for (let i = 0; i < mutationsList.length; i++) {
      if (mutationsList[i].type === 'childList') {
        const containersAdded = document.querySelectorAll('.sc-hKwCoD.hLDWMv').length;

        if (containersAdded) {
          observer.disconnect();
          containers = document.querySelectorAll('.sc-hKwCoD.hLDWMv');

          stretchContainers();

          return;
        }
      }
    }
  }

  function stretchContainers () {
    containers.forEach(container => {
      if (container.children.length === 1) {
        container.children[0].style.width = '100%'
      }
    })
  }
}