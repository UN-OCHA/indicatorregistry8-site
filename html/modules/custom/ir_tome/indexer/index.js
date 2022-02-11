const puppeteer = require('puppeteer');
const argv = require('minimist')(process.argv.slice(2), {
  'string': ['login', 'index']
});

if (!argv['login'] || !argv['index']) {
  console.error('Please specify login and index url.');
  console.info('Usage: node index.js --login=... --index=...');
  console.info('Exmaple: node index.js --login=http://indicatorregistry8-site.docksal/user/reset/1/1643896859/fV1MFiQ14NbzNrDRYltw5ox4v1bFdBYEVBy-_TIEG5U/login --index=http://indicatorregistry8-site.docksal/admin/config/lunr_search/indicators/index');
  process.exit(1);
}

(async () => {
    const browser = await puppeteer.launch();
    const page = await browser.newPage();

    const url_login = argv['login'];
    const url_index = argv['index'];

    async function waitForSelectors(selectors, frame) {
      for (const selector of selectors) {
        try {
          return await waitForSelector(selector, frame);
        } catch (err) {
          console.error(err);
        }
      }
      throw new Error('Could not find element for selectors: ' + JSON.stringify(selectors));
    }

    async function waitForSelector(selector, frame) {
      if (selector instanceof Array) {
        let element = null;
        for (const part of selector) {
          if (!element) {
            element = await frame.waitForSelector(part);
          } else {
            element = await element.$(part);
          }
          if (!element) {
            throw new Error('Could not find element: ' + part);
          }
          element = (await element.evaluateHandle(el => el.shadowRoot ? el.shadowRoot : el)).asElement();
        }
        if (!element) {
          throw new Error('Could not find element: ' + selector.join('|'));
        }
        return element;
      }
      const element = await frame.waitForSelector(selector);
      if (!element) {
        throw new Error('Could not find element: ' + selector);
      }
      return element;
    }

    {
        const targetPage = page;
        await targetPage.setViewport({"width":1470,"height":1223})
    }
    {
      const targetPage = page;
      const promises = [];
      promises.push(targetPage.waitForNavigation());
      await targetPage.goto(url_login);
      await Promise.all(promises);
    }
    {
        const targetPage = page;
        const promises = [];
        promises.push(targetPage.waitForNavigation());
        await targetPage.goto(url_index);
        await Promise.all(promises);
    }
    {
        const targetPage = page;
        const element = await waitForSelector(".js-lunr-search-index-button", targetPage);
        await element.click({ offset: { x: 49.48038864135742, y: 12.372543334960938} });
    }
    {
        const targetPage = page;
        await page.waitForXPath("//*[@class='lunr-search-index-progress' and contains(., 'Finished indexing')]");
	  }

    await browser.close();
})();
