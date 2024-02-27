'use client';
import { getCsrfToken, useSession } from 'next-auth/react';
import { signIn } from 'next-auth/react';

// Styles
import styles from '@/styles/views/auth/login.module.scss';
import { FormEvent, useEffect, useState } from 'react';
import { StatusMessage } from '@/components/StatusMessage';

interface FormElements extends HTMLFormControlsCollection {
	csrfToken: HTMLInputElement,
	username: HTMLInputElement,
	password: HTMLInputElement,
}
interface LoginForm extends HTMLFormElement {
	readonly elements: FormElements,
}

async function getData() {
	const token = await getCsrfToken();
	if (typeof token === 'undefined') {
		return new Error('Konnte den CSRF-Token nicht laden!');
	}
	return token;
}
async function authenticate(event: React.FormEvent<LoginForm>) {
	event.preventDefault();
	const res = await signIn('credentials', {
		redirect: false,
		username: event.currentTarget.elements.username.value,
		password: event.currentTarget.elements.password.value,
	});
	if (typeof res !== 'undefined') {
		if (res.ok) {
			const urlParams = new URLSearchParams(window.location.search);
			const redirect = urlParams.get('callbackUrl');
			if (redirect) {
				window.location.href = redirect;
			} else {
				window.location.href = "/";
			}
			return "";
		} else {
			return 'Die Zugangsdaten waren inkorrekt.';
		}
	}
	return 'Es konnte keine Verbindung mit dem Backend hergestellt werden.';
}
export default function Login(req: Request, res: Response) {
	const [isProcessing, setProcessing] = useState(true);
	const [csrfToken, setCsrfToken] = useState("");
	const [statusMsg, setStatusMsg] = useState("");
	useEffect(() => {
		getData().then((value) => {
			if (value instanceof Error) {
				setStatusMsg(value.message);
			} else {
				setCsrfToken(value);
			}
			setProcessing(false);
		});
	});
	return (
		<div className={styles.page}>
			<div className={styles.box}>
				{ isProcessing ? <div className={styles.loading}><span className={styles.loader}></span></div> : ""}
				<h1>Anmelden</h1>
				{ statusMsg == "" ? "" : <StatusMessage message={statusMsg} type="error" />}
				<form onSubmit={async (event: FormEvent<LoginForm>) => {
					setProcessing(true);
					const msg = await authenticate(event);
					if (msg !== null) {
						setStatusMsg(msg);
					}
					setProcessing(false);
				}}>
					<input type="hidden" name="csrfToken" defaultValue={csrfToken} />
					<label htmlFor="username">
						<input type="text" name="username" required/>
						<span>Benutzername</span>
					</label>
					<label htmlFor="password">
						<input type="password" name="password" required/>
						<span>Passwort</span>
					</label>
					<button type="submit" className='btn-primary full-width'>Anmelden</button>
				</form>
				<div className={styles.claim}>
					<div className={styles.authors}>
						<span>M. Kirchermeier</span>
						<span>M. Krug</span>
						<span>M. Polder</span>
						<span>O. Fuchs</span>
					</div>
					<p>Copyright © 2024</p>
				</div>
			</div>
		</div>
	)
}
