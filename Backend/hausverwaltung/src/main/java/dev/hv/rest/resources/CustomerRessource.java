package dev.hv.rest.resources;

import java.util.List;

import org.jdbi.v3.core.statement.UnableToExecuteStatementException;

import dev.hv.rest.model.IRCustomer;
import dev.hv.rest.model.IRUser;
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
			return Response.status(Response.Status.NO_CONTENT).entity("There is no customer with id " + id + ".")
					.build();
		} catch (Exception ex) {
			return Response.status(Response.Status.INTERNAL_SERVER_ERROR).entity(
					"There was an error while getting the customer with id = " + id + ". Details: " + ex.getMessage())
					.build();
		}
	}

	@GET
	@Path("get")
	@Produces(MediaType.APPLICATION_JSON)
	public Response getAllCustomers() {
		try {
			List<IRCustomer> customers = (new CustomerJsonUtil()).getAll();
			return Response.status(Response.Status.OK).entity(customers).build();
		} catch (Exception ex) {
			return Response.status(Response.Status.INTERNAL_SERVER_ERROR)
					.entity("There was an error while getting all of the customers. Details: " + ex.getMessage())
					.build();
		}
	}

	@DELETE
	@Path("delete/{id}")
	@Produces(MediaType.APPLICATION_JSON)
	public Response deleteCustomer(@PathParam("id") int id) {
		try {
			new CustomerJsonUtil().delete(id);
			return Response.status(Response.Status.OK).build();
		} catch (Exception ex) {
			return Response.status(Response.Status.INTERNAL_SERVER_ERROR).entity(
					"There was an error while deleting the customer with id = " + id + ". Details: " + ex.getMessage())
					.build();
		}
	}

	@POST
	@Path("create")
	@Consumes(MediaType.APPLICATION_JSON)
	@Produces(MediaType.APPLICATION_JSON)
	public Response createCustomer(RCustomer customer) {
		try {
			CustomerJsonUtil util = new CustomerJsonUtil();
			int created = util.insert(customer);
			if (created > 0) {
				return Response.status(Response.Status.CREATED).entity(customer).build();
			}
			return Response.status(Response.Status.INTERNAL_SERVER_ERROR)
					.entity("There was an Error inserting the user into the database!").build();
		} catch (UnableToExecuteStatementException sqlException) {
			if (sqlException.getMessage().contains("[SQLITE_CONSTRAINT_PRIMARYKEY] A PRIMARY KEY constraint failed")) {
				return Response.status(Response.Status.CONFLICT)
						.entity("There is already a user with the id " + customer.getId() + "!").build();
			} else
				throw sqlException;
		} catch (Exception ex) {
			ex.printStackTrace();
			return Response.status(Response.Status.INTERNAL_SERVER_ERROR)
					.entity("There was an error while creating the new user. Details: " + ex.getMessage()).build();
		}
	}

	@POST
	@Path("update/{id}")
	@Consumes(MediaType.APPLICATION_JSON)
	@Produces(MediaType.APPLICATION_JSON)
	public Response updateUser(@PathParam("id") int id, RCustomer customer) {
		CustomerJsonUtil util = new CustomerJsonUtil();
		try {
			if (customer == null) {
				return Response.status(Response.Status.BAD_REQUEST)
						.entity("There was no new customer information given!").build();
			}

			IRCustomer dbCustomer = util.getWithID(id);
			if (customer.getFirstname() != null)
				dbCustomer.setFirstname(customer.getFirstname());
			if (customer.getLastname() != null)
				dbCustomer.setLastname(customer.getLastname());
			util.update(dbCustomer);
			return Response.status(Response.Status.OK).entity(dbCustomer).build();
		} catch (NullPointerException eNullPointerException) {
			return Response.status(Response.Status.NO_CONTENT)
					.entity("There is no user with the id " + id + " to update!").build();
		} catch (Exception ex) {
			return Response.status(Response.Status.INTERNAL_SERVER_ERROR)
					.entity("There was an error while updating the new user. Details: " + ex.getMessage()).build();
		}
	}

}
