'use client';

import styles from '@/styles/components/navigation.module.scss';
import SignOutButton from './SignOutButton';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faBars, faClose, faHome, faPerson } from '@fortawesome/free-solid-svg-icons';

const toggleNavigation = () => {
	const navElement = document.querySelector(`.${styles.navigationContent}`);
	if (navElement != null) {
		if (navElement.classList.contains(styles.open)) {
			navElement.classList.remove(styles.open);
		} else {
			navElement.classList.add(styles.open);
		}
	}
	console.log(navElement);
}

export function Navigation() {
	return (
		<div className={styles.navigation}>
			<div className={styles.frontpage}>
				<a href="/">
					<span className={styles.icon}><FontAwesomeIcon icon={ faHome } width={48} height={48}/></span>
				</a>
			</div>
			<nav>
				<ul className={styles.navigationContent}>
					<li className={[styles.toggleNavigation, styles.navigationItem].join(" ")} onClick={toggleNavigation}>
						<a href="#">
							<span className={styles.icon}><FontAwesomeIcon icon={ faClose } /></span>
						</a>
					</li>
					<li className={styles.navigationItem}>
						<a href="/customers">
							Kunden
						</a>
					</li>
					<li className={styles.navigationItem}>
						<a href="/users">
							Benutzer
						</a>
					</li>
					<li className={styles.navigationItem}>
						<a href="/readings">
							Zählerstände
						</a>
					</li>
					<li className={styles.navigationItem}>
						<SignOutButton/>
					</li>
				</ul>
			</nav>
			<div className={styles.toggleNavigation} onClick={toggleNavigation}>
				<span className={styles.icon}><FontAwesomeIcon icon={ faBars }/></span>
			</div>
		</div>
	);
}