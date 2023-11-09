package dev.hv.rest.resources;

import java.util.List;

import dev.hv.rest.model.IRCustomer;
import dev.hv.rest.model.RCustomer;
import dev.hv.rest.model.RUser;
import dev.hv.rest.util.CustomerJsonUtil;
import dev.hv.rest.util.UserJsonUtil;
import jakarta.ws.rs.Consumes;
import jakarta.ws.rs.DELETE;
import jakarta.ws.rs.FormParam;
import jakarta.ws.rs.GET;
import jakarta.ws.rs.POST;
import jakarta.ws.rs.PUT;
import jakarta.ws.rs.Path;
import jakarta.ws.rs.PathParam;
import jakarta.ws.rs.Produces;
import jakarta.ws.rs.core.MediaType;
import jakarta.ws.rs.core.Response;

@Path("customers")
public class CustomerRessource {

	@GET
	@Path("get/{id}")
	@Produces(MediaType.APPLICATION_JSON)
	public Response getCustomerById(@PathParam("id") int id) {
		try {
			IRCustomer customer = (new CustomerJsonUtil()).getWithID(id);
			return Response.status(Response.Status.OK).entity(customer).build();
		} catch (NullPointerException eNullPointerException) {
			return Response.status(Response.Status.NO_CONTENT).entity("There is no customer with id " + id + ".").build();
		}
	}

	@GET
	@Path("get")
	@Produces(MediaType.APPLICATION_JSON)
	public Response getAllCustomers() {
		List<IRCustomer> customers = (new CustomerJsonUtil()).getAll();
		return Response.status(Response.Status.OK).entity(customers).build();
	}

	@DELETE
	@Path("delete/{id}")
	@Produces(MediaType.APPLICATION_JSON)
	public Response deleteCustomer(@PathParam("id") int id) {
		new CustomerJsonUtil().delete(id);
		return Response.status(Response.Status.OK).build();
	}
	
	@POST
	@Path("create")
	@Consumes(MediaType.APPLICATION_JSON)
	@Produces(MediaType.APPLICATION_JSON)
	public Response createCustomer(RCustomer customer) {
		CustomerJsonUtil util = new CustomerJsonUtil();
		System.out.println(customer.getId());
		int created = util.insert(customer);
		if (created > 0) {
			return Response.status(Response.Status.CREATED).entity(customer).build();
		}
		return Response.status(Response.Status.INTERNAL_SERVER_ERROR).entity("There was an Error inserting the user into the database!").build();
	}

}
