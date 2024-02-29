import axios, { AxiosError } from "axios";
import { StatusMessage } from "./StatusMessage";
import { CustomerData } from "@/types";
import tableStyles from '@/styles/components/table.module.scss';
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import { faEdit, faTrash } from "@fortawesome/free-solid-svg-icons";
import EditCustomerPopup from "./EditCustomerPopup";

export default async function CustomerTable() {
	var response;
	try {
		response = await axios.get("http://localhost:8080/rest/customers/get");
	} catch (err: unknown) {
		if (err instanceof AxiosError && err.code == 'ECONNREFUSED') {
			return <StatusMessage message="Es konnte keine Verbindung zum Backend hergestellt werden." type="error"/>
		} else {
			throw err;
		}
	}
	if (response.status !== 200) return <StatusMessage message="Es konnte keine Verbindung zum Backend hergestellt werden." type="error"/>;
	
	const responseData = response.data;
	
	return <div className={tableStyles.table}>
		<table>
			<thead>
				<tr>
					<th>ID</th>
					<th>Vorname</th>
					<th>Nachname</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				{
					responseData.length == 0 ?
						<StatusMessage message="Keine Daten verfügbar" type="info"/>
					: responseData.map((value: CustomerData, index: number) => {
						return <tr key={index}>
							<td>{value.id}</td>
							<td>{value.firstname}</td>
							<td>{value.lastname}</td>
							<td className={tableStyles.rowActions}>
								<EditCustomerPopup data={value} />
								<button>
									<FontAwesomeIcon icon={ faTrash }/>
								</button>
							</td>
						</tr>
					})
				}
			</tbody>
		</table>
	</div>
}