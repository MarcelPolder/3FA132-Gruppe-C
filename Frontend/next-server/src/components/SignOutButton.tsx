'use client';

import { faRightFromBracket } from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import { signOut } from "next-auth/react";

export default function SignOutButton() {
	return (
		<a href="" onClick={(event) => {
			event.preventDefault();
			signOut();
		}}>
			<span><FontAwesomeIcon icon={faRightFromBracket} width={24} height={24}/></span>Abmelden
		</a>
	);
}