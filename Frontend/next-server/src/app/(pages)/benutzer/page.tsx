'use client';

import { StatusMessage } from "@/components/StatusMessage";
import UserInfo, { UserData, UserForm } from "@/components/UserInfo";
import { faSave } from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import axios from "axios";
import { useEffect, useState } from "react";

const getData = async () => {
	const response = await fetch("/api/users");
	if (response.ok) {
		return await response.json();
	} else {
		return "Ein Fehler ist aufgetreten."
	}
}
const genRanHex = (size: number) => [...Array(size)].map(() => Math.floor(Math.random() * 16).toString(16)).join('');

export default function Benutzer() {
	const [statusMsg, setStatusMsg] = useState("");
	const [data, setData] = useState([]);

	useEffect(() => {
		getData().then((resp) => {
			if (typeof resp === 'string') {
				setStatusMsg(resp);
			} else {
				setData(resp);
			}
		});
	});
	
	const formSubmit = async (event: React.FormEvent<UserForm>) => {
		event.preventDefault();
		const response = await axios.post("/api/users/create", {
			firstname: event.currentTarget.elements.firstname.value,
			lastname: event.currentTarget.elements.lastname.value,
			password: event.currentTarget.elements.password.value,
			token: genRanHex(16)
		});
		if (response.status == 200) {
			setStatusMsg("Der Benutzer wurde erfolgreich erstellt.");
		} else {
			setStatusMsg("Etwas ist schiefgelaufen");
		}
	}

	return (
		<>
			<section>
				<h2>Benutzerverwaltung</h2>
				{ statusMsg == "" ? "" : <StatusMessage message={statusMsg} type='error' /> }
				{
					data.map((value: UserData) => {
						return <UserInfo data={value} key={value.id}/>
					})
				}
				<br />
			</section>
			<section>
				<h3>Benutzer erstellen</h3>
				<form onSubmit={formSubmit}>
					<label>
						<span>Vorname</span>
						<input type="text" name="firstname" required/>
					</label>
					<label>
						<span>Nachname</span>
						<input type="text" name="lastname" required/>
					</label>
					<label>
						<span>Passwort</span>
						<input type="password" name="password" required/>
					</label>
					<br />
					<button type="submit">
						<span><FontAwesomeIcon icon={ faSave }/></span>Erstellen
					</button>
				</form>
			</section>
		</>
	);
}