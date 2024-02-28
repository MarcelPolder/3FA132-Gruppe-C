'use client';
import styles from '@/styles/components/user-info.module.scss';
import { faCheckCircle, faTrash } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { MouseEventHandler, useState } from 'react';
import { StatusMessage } from './StatusMessage';
import axios from 'axios';

export interface UserData {
	id: number|string;
	firstname: string;
	lastname: string;
	password: string;
	token: string;
}

interface FormElements extends HTMLFormControlsCollection {
	id: HTMLInputElement;
	firstname: HTMLInputElement;
	lastname: HTMLInputElement;
	password: HTMLInputElement;
	token: HTMLInputElement;
}
export interface UserForm extends HTMLFormElement {
	readonly elements: FormElements
}

export default function UserInfo({ data }: { data: UserData}) {
	const inputData = data;
	const [statusMsg, setStatusMsg] = useState("");

	const formSubmit = async (event: React.FormEvent<UserForm>) => {
		event.preventDefault();
		const response = await axios.post('/api/users/update', {
				id: event.currentTarget.elements.id.value,
				firstname: event.currentTarget.elements.firstname.value,
				lastname: event.currentTarget.elements.lastname.value,
				password: event.currentTarget.elements.password.value,
		});
		if (response.status==200) {
			setStatusMsg("Der Benutzer wurde erfolgreich geändert.");
		} else {
			const data = response.data
			setStatusMsg(data.msg);
		}
	}

	const deleteUser: MouseEventHandler<HTMLButtonElement> = async (event) => {
		event.preventDefault();
		const id = event.currentTarget.attributes.getNamedItem("data-id")?.value;
		if (id !== null) {
			const response = await axios.post("/api/users/delete", {id: id});
			if (response.status == 200) {
				setStatusMsg("Der Benutzer wurde erfolgreich gelöscht.")
			} else {
				setStatusMsg(response.data.msg);
			}
		}
	}

	return (
		<div className={styles.userInfo} data-id={data.id}>
			<h3>{data.firstname}, {data.lastname}</h3>
			{ statusMsg == '' ? '' : <StatusMessage message={statusMsg} type='info' /> }
			<form onSubmit={formSubmit}>
				<input
					type="hidden"
					name="id"
					value={data.id}
				/>
				<div className={styles.col2}>
					<label>
						<span>Vorname</span>
						<input
							type="text"
							name="firstname"
							defaultValue={data.firstname}
							onChange={((event) => {
								inputData.firstname = event.currentTarget.value;
							})}
						/>
					</label>
					<label>
						<span>Nachname</span>
						<input
							type="text"
							name="lastname"
							defaultValue={data.lastname}
							onChange={((event) => {
								inputData.lastname = event.currentTarget.value;
								event.preventDefault();
							})}
						/>
					</label>
				</div>
				<label>
					<span>Passwort</span>
					<input
						type="password"
						name="password"
						onChange={((event) => {
							inputData.password = event.currentTarget.value;
						})}
					/>
				</label>
				<button type="submit" name="update-user" className={styles.submitBtn}>
					<span className={styles.icon}><FontAwesomeIcon icon={ faCheckCircle }/></span>Aktualisieren
				</button>
				<button name="delete-user" data-id={data.id} className={styles.submitBtn} onClick={deleteUser}>
					<span className={styles.icon}><FontAwesomeIcon icon={ faTrash }/></span>Löschen
				</button>
			</form>
		</div>
	);
}