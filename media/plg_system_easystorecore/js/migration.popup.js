window.addEventListener('DOMContentLoaded', async () => {
	const VALID_MIGRATORS = ['com_j2store', 'com_cpanel'];
	const html = await fetchData();
	const searchParams = new URLSearchParams(window.location.href);
	const isMigrationPage = searchParams.has('migration');

	if (isMigrationPage) {
		return;
	}

	const bodyClasses = document.body.classList;
	const bodyClass = VALID_MIGRATORS.find((migrator) => bodyClasses.contains(migrator));

	if (!bodyClass) {
		return;
	}

	const easyStoreBody = document.querySelector(`.${bodyClass}`);
	easyStoreBody.insertAdjacentHTML('beforeend', html);

	const popupTitleWrapper = document.querySelector('.easystore-migration-title-wrapper');
	const svgIcon = popupTitleWrapper.querySelector('svg');
	const popupContentWrapper = document.querySelector('.easystore-migration-content-wrapper');

	checkEasyStorePopupStatus();

	popupTitleWrapper.addEventListener('click', () => {
		const isClosed = popupContentWrapper.classList.toggle('easystore-hidden');
		localStorage.setItem('easystore-migration-popup-open', isClosed ? 'close' : 'open');
		svgIcon.classList.toggle('easystore-rotate', isClosed);
	});

	function checkEasyStorePopupStatus() {
		let localStoragePopupStatus = localStorage.getItem('easystore-migration-popup-open');

		if (!localStoragePopupStatus) {
			localStorage.setItem('easystore-migration-popup-open', 'open');
			localStoragePopupStatus = 'open';
		}

		const isPopupHidden = localStoragePopupStatus === 'open' ? false : true;

		popupContentWrapper.classList.toggle('easystore-hidden', isPopupHidden);
		svgIcon.classList.toggle('easystore-rotate', isPopupHidden);
	}

	async function fetchData() {
		const BASE_URL = Joomla.getOptions('easystore.base');

		try {
			const response = await fetch(`${BASE_URL}/administrator/index.php?option=com_easystore&task=migration.popup`);
			const data = await response.text();
			return data;
		} catch (error) {
			console.error(error);
		}
	}
});
