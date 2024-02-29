import { CustomerData } from "@/types";
import axios from "axios";
import { NextRequest, NextResponse } from "next/server";

async function handler(
	req: NextRequest,
	res: NextResponse,
) {

	if (req.method == 'POST') {
		const data: CustomerData = await req.json();
		const response = await axios.post(`http://localhost:8080/rest/customers/update/${data.id}`, {
			customer: {
				firstname: data.firstname,
				lastname: data.lastname,
			}
		}, {
			headers: {
				'Content-Type': 'application/json'
			}
		});
		if (response.status == 200) {
			return NextResponse.json({status: 200, msg: 'Der Kunde wurde erfolgreich bearbeitet.'});
		}
		return NextResponse.json({status: 500, msg: 'Ein Fehler ist aufgetreten.'});
	}

	return NextResponse.error();
}

export { handler as GET, handler as POST };