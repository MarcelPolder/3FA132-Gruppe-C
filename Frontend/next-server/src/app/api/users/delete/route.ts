import axios from "axios";
import { NextRequest, NextResponse } from "next/server";

async function handler(
	req: NextRequest,
	res: NextResponse,
) {
	if (req.method == 'POST') {
		const body = await req.json();
		if (typeof body.id !== 'undefined') {
			const response = await axios.delete(`http://localhost:8080/rest/users/delete/${body.id}`);
			if (response.status == 200) {
				return NextResponse.json({status: 200, msg: 'Der Benutzer wurde erfolgreich gelöscht.'});
			}
		}
	}
	return NextResponse.error();
}

export { handler as GET, handler as POST };