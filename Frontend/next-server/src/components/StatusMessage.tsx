import styles from '@/styles/components/status-message.module.scss';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faExclamationCircle, faInfoCircle } from '@fortawesome/free-solid-svg-icons';

export interface StatusMessageOptions {
	message: string;
	type: 'error' | 'info'
}
export function StatusMessage({message, type}: StatusMessageOptions) {
	return (<>
		<div className={`${ styles.message } ${ type=='error' ? styles.error : styles.info }`}>
			<span className={ styles.icon }><FontAwesomeIcon icon={ type=='error' ? faExclamationCircle : faInfoCircle }/></span>
			<span className={ styles.text }>{message}</span>
		</div>
	</>);
}