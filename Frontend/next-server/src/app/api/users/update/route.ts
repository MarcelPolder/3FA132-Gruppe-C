import { UserData } from "@/components/UserInfo";
import axios from "axios";
import { NextApiRequest, NextApiResponse } from "next";
import { NextRequest, NextResponse } from "next/server";

async function handler(
	req: NextRequest,
	res: NextApiResponse
) {
	if (req.method == 'POST') {
		const data: UserData = await req.json();
		
		const response = await axios.post(`http://localhost:8080/rest/users/update/${data.id}`, data, {
			headers: {
				"Content-Type": "application/x-www-form-urlencoded",
			}
		})
		if (response.status == 200) {
			return NextResponse.json({status: 200, msg: 'Der Benutzer wurde erfolgreich geändert.'});
		} else {
			return NextResponse.json(response.data);
		}
	} else {

	}
}

export { handler as GET, handler as POST };