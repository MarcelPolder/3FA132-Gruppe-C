package dev.hv.rest.model;

import com.fasterxml.jackson.annotation.JsonProperty;
import com.fasterxml.jackson.annotation.JsonTypeInfo;
import com.fasterxml.jackson.annotation.JsonTypeName;

import dev.hv.db.model.IDUser;
import lombok.AllArgsConstructor;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;
import lombok.ToString;

@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
@ToString
@JsonTypeInfo(include = JsonTypeInfo.As.WRAPPER_OBJECT, use = JsonTypeInfo.Id.NAME)
@JsonTypeName(value = "user")
public class RUser implements IRUser {

	@JsonProperty
	String Firstname;

	@JsonProperty
	Integer Id;

	@JsonProperty
	String Lastname;

	@JsonProperty
	String Password;

	@JsonProperty
	String Token;
	
	public RUser(IDUser dbUser) {
		setId(dbUser.getId());
		setFirstname(dbUser.getFirstname());
		setLastname(dbUser.getLastname());
		setPassword(dbUser.getPassword());
		setToken(dbUser.getToken());
	}
}
