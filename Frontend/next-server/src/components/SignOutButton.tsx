'use client';

import { signOut } from "next-auth/react";

export default function SignOutButton() {
	return (
		<a href="" onClick={(event) => {
			event.preventDefault();
			signOut();
		}}>
			Abmelden
		</a>
	);
}