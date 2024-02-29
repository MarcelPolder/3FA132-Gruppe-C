import styles from '@/styles/components/popup.module.scss';
import { faClose } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { MouseEventHandler } from 'react';

const genRanHex = (size: number) => [...Array(size)].map(() => Math.floor(Math.random() * 16).toString(16)).join('');

const togglePopup: MouseEventHandler<HTMLDivElement> = (event) => {
	const element = event.currentTarget;
	const popupId = element.getAttribute("data-popup");
	if (popupId === null) return false;

	const popup = document.querySelector(popupId);
	if (popup === null) return false;

	if (popup.classList.contains(styles.visible)) {
		popup.classList.remove(styles.visible);
	} else {
		popup.classList.add(styles.visible);
	}
	return true;
}

export default function PopUp({ children, id }: { children: React.ReactNode, id: string }) {
	return <div className={styles.popup} id={`${id}`}>
		<div className={styles.popupBox}>
			<div className={styles.popupBoxClose} onClick={togglePopup} data-popup={`#${id}`}>
				<FontAwesomeIcon icon={ faClose } />
			</div>
			<div className={styles.popupBoxScroll}>
				{children}
			</div>
		</div>
	</div>
}