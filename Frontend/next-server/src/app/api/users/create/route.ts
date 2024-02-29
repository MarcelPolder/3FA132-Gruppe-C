import axios from "axios";
import { NextApiResponse } from "next";
import { NextRequest, NextResponse } from "next/server";

async function handler(
	req: NextRequest,
	res: NextApiResponse,
) {
	if (req.method == 'POST') {
		const data = await req.json();
		const response = await axios.post("http://localhost:8080/rest/users/create", {user: data}, {
			headers: {
				"Content-Type": 'application/json',
			}
		});
		if (response.status == 200) {
			return NextResponse.json({ status: 200, msg: 'Der Benutzer wurde erfolgreich erstellt.'});
		}
	}
	return NextResponse.json({	status: 500, msg:'Kein erfolg'});
}

export { handler as GET, handler as POST };
