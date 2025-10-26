window.addEventListener('DOMContentLoaded', async () => {
	const html = await fetchData();
	const domParser = new DOMParser();
	const doc = domParser.parseFromString(html, 'text/html');

	const settingstepsPopupState = localStorage.getItem('easystore-settingsteps-popup-dismissed');
	const isAllCompleted = doc.querySelector('[easystore-settingsteps-isAllCompleted]');

	if (settingstepsPopupState && isAllCompleted) {
		return;
	}

	document.body.insertAdjacentHTML('beforeend', html);

	const popupEl = document.querySelector('.easystore-settingsteps-popup');
	const popupTitleWrapper = document.querySelector('.easystore-settingsteps-title-wrapper');
	const svgIcon = popupTitleWrapper.querySelector('svg:last-of-type');
	const popupContentWrapper = document.querySelector('.easystore-settingsteps-content-wrapper');

	checkEasyStorePopupStatus();

	popupTitleWrapper.addEventListener('click', (e) => {
		if (e.target.closest('[easystore-settingsteps-popup-close]')) {
			popupEl.remove();
			localStorage.setItem('easystore-settingsteps-popup-dismissed', 'true');
			return;
		}
		const isClosed = popupContentWrapper.classList.toggle('easystore-hidden');
		localStorage.setItem('easystore-settingsteps-popup-open', isClosed ? 'close' : 'open');
		svgIcon.classList.toggle('easystore-rotate', isClosed);
	});

	function checkEasyStorePopupStatus() {
		let localStoragePopupStatus = localStorage.getItem('easystore-settingsteps-popup-open');

		if (settingstepsPopupState) {
			localStorage.removeItem('easystore-settingsteps-popup-dismissed');
		}

		if (!localStoragePopupStatus) {
			localStorage.setItem('easystore-settingsteps-popup-open', 'open');
			localStoragePopupStatus = 'open';
		}

		const isPopupHidden = localStoragePopupStatus === 'open' ? false : true;

		popupContentWrapper.classList.toggle('easystore-hidden', isPopupHidden);
		svgIcon.classList.toggle('easystore-rotate', isPopupHidden);
	}

	async function fetchData() {
		const BASE_URL = Joomla.getOptions('easystore.base');

		try {
			const response = await fetch(
				`${BASE_URL}/administrator/index.php?option=com_easystore&task=migration.popup&type=easystore_settingsteps`,
			);
			const data = await response.text();
			return data;
		} catch (error) {
			console.error(error);
		}
	}
});
