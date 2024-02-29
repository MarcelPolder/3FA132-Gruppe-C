import styles from '@/styles/components/loader.module.scss';
export default function Loader() {
	return (
		<div className={styles.loader}><div className={styles.ldsRing}><div></div><div></div><div></div><div></div></div></div>
	);
}