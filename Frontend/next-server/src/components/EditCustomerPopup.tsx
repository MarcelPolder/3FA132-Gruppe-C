'use client';

import { ApiResponse, CustomerData } from "@/types";
import { faEdit } from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import PopUp from "./PopUp";
import { FormEventHandler, MouseEventHandler, useState } from "react";
import popupStyles from '@/styles/components/popup.module.scss';
import Grid from "./Grid";
import Icon from "./Icon";
import axios from "axios";

interface CustomerFormElements extends HTMLFormControlsCollection {
	id: HTMLInputElement;
	firstname: HTMLInputElement;
	lastname: HTMLInputElement;
}

interface CustomerForm extends HTMLFormElement {
	readonly elements: CustomerFormElements;
}

export default function EditCustomerPopup({ data }: { data: CustomerData }) {

	const [firstname, setFirstname] = useState(data.firstname);
	const [lastname, setLastname] = useState(data.lastname);

	const togglePopup: MouseEventHandler<HTMLButtonElement> = (event) => {
		const element = event.currentTarget;
		const popupId = element.getAttribute("data-popup");
		if (popupId === null) return false;

		const popup = document.querySelector(popupId);
		if (popup === null) return false;

		if (popup.classList.contains(popupStyles.visible)) {
			popup.classList.remove(popupStyles.visible);
		} else {
			popup.classList.add(popupStyles.visible);
		}
		return true;
	}

	const formSubmit: FormEventHandler<CustomerForm> = async (event) => {
		event.preventDefault();
		const data = {
			id: event.currentTarget.elements.id.value,
			firstname: firstname,
			lastname: lastname,
		};

		const response: ApiResponse = await axios.post("/api/customers/update", data);
	}

	return (
		<>
			<button name="edit-customer" data-popup={`#customer-${data.id}`} onClick={ togglePopup }>
				<FontAwesomeIcon icon={ faEdit }/>
			</button>
			<PopUp id={`customer-${data.id}`}>
				<form onSubmit={formSubmit} method="POST">
					<input type="hidden" name="id" value={data.id} />
					<Grid columnsM={2}>
						<label>
							<span>Vorname</span>
							<input type="text" name="firstname" defaultValue={data.firstname} onChange={(event) => {
								setFirstname(event.currentTarget.value);
							}} />
						</label>
						<label>
							<span>Nachname</span>
							<input type="text" name="lastname" defaultValue={data.lastname} onChange={(event) => {
								setLastname(event.currentTarget.value);
							}} />
						</label>
					</Grid>
					<button type="submit" name="edit-customer">
						<Icon icon={ faEdit } marginR={12}/>Bearbeiten
					</button>
				</form>
			</PopUp>
		</>
	);
}