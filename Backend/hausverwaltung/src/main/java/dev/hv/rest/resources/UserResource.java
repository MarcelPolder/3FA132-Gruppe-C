package dev.hv.rest.resources;

import dev.hv.db.model.IDUser;
import dev.hv.rest.model.IRUser;
import dev.hv.rest.model.RUser;
import dev.hv.rest.util.UserJsonUtil;

import java.util.List;

import org.jdbi.v3.core.statement.UnableToExecuteStatementException;
import org.sqlite.SQLiteException;

import jakarta.ws.rs.ApplicationPath;
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

@Path("users")
public class UserResource {
	@GET
	@Path("get/{id}")
	@Produces(MediaType.APPLICATION_JSON)
	public Response getUserById(@PathParam("id") int id) {
		try {
			IRUser user = (new UserJsonUtil()).getWithID(id);
			return Response.status(Response.Status.OK).entity(user).build();
		} catch (NullPointerException eNullPointerException) {
			return Response.status(Response.Status.NO_CONTENT).entity("There is no user with id "+id+".").build();
		}
	}

	@GET
	@Path("get")
	@Produces(MediaType.APPLICATION_JSON)
	public Response getAllUsers() {
		List<IRUser> users = (new UserJsonUtil()).getAll();
		return Response.status(Response.Status.OK).entity(users).build();
	}

	@DELETE
	@Path("delete/{id}")
	@Produces(MediaType.APPLICATION_JSON)
	public Response deleteUser(@PathParam("id") int id) {
		UserJsonUtil util = new UserJsonUtil();
		util.delete(id);
		return Response.status(Response.Status.OK).build();
	}

	@POST
	@Path("create")
	@Consumes(MediaType.APPLICATION_JSON)
	public Response createUser(RUser user) {

		UserJsonUtil util = new UserJsonUtil();
		try {
			int created = util.insert(user);
			if (created > 0) {
				return Response.status(Response.Status.CREATED).entity(user).build();
			}
			return Response.status(Response.Status.INTERNAL_SERVER_ERROR).entity("There was an Error inserting the user into the database!").build();
		} catch (UnableToExecuteStatementException sqLiteException) {
			return Response.status(Response.Status.INTERNAL_SERVER_ERROR).entity("A User with the id "+user.getId()+" already exists!").build();
		}
	}

	@POST
	@Path("update/{id}")
	@Consumes(MediaType.APPLICATION_JSON)
	@Produces(MediaType.APPLICATION_JSON)
	public Response updateUser(RUser user) {
		UserJsonUtil util = new UserJsonUtil();
		try {
			util.update(user);
			return Response.status(Response.Status.OK).entity(user).build();
		} catch (NullPointerException eNullPointerException) {
			return Response.status(Response.Status.NO_CONTENT).entity("There is no user with the id "+user.getId()+" to update!").build();
		}
	}
}