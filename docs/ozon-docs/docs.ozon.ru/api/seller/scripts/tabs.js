export const tabs = () => {
  let tabs;
  let switches;

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
        const tabsAdded = document.querySelectorAll('.tabs');

        if (tabsAdded) {
          observer.disconnect();
          tabs = document.querySelectorAll('.tabs');
          switches = document.querySelectorAll('.tabs input');

          setUniqueId();
          setUniqueFor();
          setUniqueName();
        }
      }
    }
  }

  function setUniqueId () {
    switches.forEach((item, i) => {
      item.id += i;
    });
  }

  function setUniqueFor () {
    const labels = document.querySelectorAll('.tabs label');

    labels.forEach((item, i) => {
      item.htmlFor += i;
    });
  }

  function setUniqueName () {
    tabs.forEach((item, i) => {
      const switches = item.querySelectorAll('input')

      switches.forEach((item, switchIndex) => {
        if (!switchIndex) item.checked = true;

        item.name += i
      })
    })
  }
}
